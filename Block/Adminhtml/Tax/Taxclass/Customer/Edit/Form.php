<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2022 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Block\Adminhtml\Tax\Taxclass\Customer\Edit;

use Taxjar\SalesTax\Model\Configuration;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Tax\Block\Adminhtml\Rate\Title\FieldsetFactory
     */
    protected $fieldsetFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    protected $taxClassRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Tax\Block\Adminhtml\Rate\Title\FieldsetFactory $fieldsetFactory
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Tax\Block\Adminhtml\Rate\Title\FieldsetFactory $fieldsetFactory,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        array $data = []
    ) {
        $this->formKey = $context->getFormKey();
        $this->regionFactory = $regionFactory;
        $this->fieldsetFactory = $fieldsetFactory;
        $this->taxClassRepository = $taxClassRepository;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init class
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('taxClassForm');
        $this->setTitle(__('Customer Tax Class Information'));
        $this->setUseContainer(true);
    }

    /**
     * @inheritDoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $taxClassId = $this->_coreRegistry->registry('tax_class_id');
        $url = \Taxjar\SalesTax\Model\Configuration::TAXJAR_MAGETWO_GUIDE_URL;
        $url .= '/#section-customer-sales-tax-exemptions';

        try {
            if ($taxClassId) {
                $taxClass = $this->taxClassRepository->get($taxClassId);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $taxClass = null;
        }

        $sessionFormValues = (array)$this->_coreRegistry->registry('tax_class_form_data');
        $taxClassData = isset($taxClass) ? $this->extractTaxClassData($taxClass) : [];
        $formValues = array_merge($taxClassData, $sessionFormValues);

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $legend = $this->getShowLegend() ? __('Tax Class Information') : '';
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => $legend, 'class' => 'form-inline']);

        if (isset($formValues['class_id']) && $formValues['class_id'] > 0) {
            $fieldset->addField(
                'class_id',
                'hidden',
                ['name' => 'class_id', 'value' => $formValues['class_id']]
            );
        }

        $fieldset->addField(
            'class_name',
            'text',
            [
                'name' => 'class_name',
                'label' => __('Class Name'),
                'required' => true,
                'value' => isset($formValues['class_name']) ? $formValues['class_name'] : ''
            ]
        );

        $fieldset->addField(
            'class_type',
            'hidden',
            ['name' => 'class_type', 'value' => 'CUSTOMER']
        );

        $fieldset->addField(
            'tj_salestax_code',
            'select',
            [
                'name' => 'tj_salestax_code',
                'label' => __('TaxJar Exempt'),
                'note' => __(
                    'Fully exempts customer groups associated with this tax class from sales tax calculations
                    through the TaxJar API. This setting does not apply to product exemptions or backup rates.
                    <b>Recommended: Use our new <a href="' . $url . '" target="_blank">customer exemptions</a>
                    feature to exempt customer transactions for sales tax reporting and filing.</b>'
                ),
                'values' => [
                    Configuration::TAXJAR_EXEMPT_TAX_CODE => 'Yes',
                    '' => 'No'
                ],
                'value' => isset($formValues['tj_salestax_code']) ? $formValues['tj_salestax_code'] : ''
            ]
        );

        $form->setAction($this->getUrl('taxjar/taxclass_customer/save'));
        $form->setUseContainer($this->getUseContainer());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Extract tax class data in a format which is
     *
     * @param \Magento\Tax\Api\Data\TaxClassInterface $taxClass
     * @return array
     */
    protected function extractTaxClassData($taxClass)
    {
        $taxClassData = [
            'class_id' => $taxClass->getClassId(),
            'class_name' => $taxClass->getClassName(),
            'tj_salestax_code' => $taxClass->getTjSalestaxCode()
        ];
        return $taxClassData;
    }
}
