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
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Block\Adminhtml\Tax\TaxClass;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Tax\Block\Adminhtml\Rate\Title\FieldsetFactory
     */
    protected $_fieldsetFactory;
    
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    protected $_taxClassRepository;
    
    /**
     * @var \Taxjar\SalesTax\Model\Config\TaxClass\Source\CategoryFactory
     */
    protected $_taxClassCategoryFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Tax\Block\Adminhtml\Rate\Title\FieldsetFactory $fieldsetFactory
     * @param \Magento\Tax\Api\TaxRateRepositoryInterface $taxRateRepository
     * @param \Taxjar\SalesTax\Model\Config\TaxClass\Source\CategoryFactory $taxClassCategoryFactory
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
        \Taxjar\SalesTax\Model\Config\TaxClass\Source\CategoryFactory $taxClassCategoryFactory,
        array $data = []
    ) {
        $this->formKey = $context->getFormKey();
        $this->_regionFactory = $regionFactory;
        $this->_fieldsetFactory = $fieldsetFactory;
        $this->_taxClassRepository = $taxClassRepository;
        $this->_taxClassCategoryFactory = $taxClassCategoryFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $taxClassId = $this->_coreRegistry->registry('tax_class_id');

        try {
            if ($taxClassId) {
                $taxClass = $this->_taxClassRepository->get($taxClassId);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            /* tax rate not found */
        }

        $sessionFormValues = (array)$this->_coreRegistry->registry('tax_class_form_data');
        $taxClassData = isset($taxClass) ? $this->extractTaxClassData($taxClass) : [];
        $formValues = array_merge($taxClassData, $sessionFormValues);

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

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
            ['name' => 'class_type', 'value' => 'PRODUCT']
        );
        
        $categoryModel = $this->_taxClassCategoryFactory->create();
        $fieldset->addField(
            'tj_salestax_code',
            'select',
            [
                'name' => 'tj_salestax_code',
                'label' => __('TaxJar Category'),
                'values' => $categoryModel->toOptionArray(),
                'value' => isset($formValues['tj_salestax_code']) ? $formValues['tj_salestax_code'] : ''
            ]
        );

        $form->setId('taxClassForm');
        $form->setTitle(__('Product Tax Class Information'));
        $form->setAction($this->getUrl('taxjar/taxClass/save'));
        $form->setUseContainer(true);
        $form->setMethod('post');
        $this->setForm($form);

        return parent::_prepareForm();
    }
    
    /**
     * Extract tax class data in a format which is
     *
     * @param \Magento\Tax\Api\Data\TaxRuleInterface $taxRule
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
