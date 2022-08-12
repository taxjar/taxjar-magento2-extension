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

namespace Taxjar\SalesTax\Block\Adminhtml\TransactionSync;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    public const FORM_ELEMENT_ID = 'transaction-sync-form';

    /**
     * @var null
     */
    protected $_titles = null;

    /**
     * @var string
     */
    protected $_template = 'transaction_sync/form.phtml';

    /**
     * @inheritDoc
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setDestElementId(self::FORM_ELEMENT_ID);
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
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $legend = $this->getShowLegend() ? __('Sync Date Range') : '';
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => $legend, 'class' => 'form-inline']);

        $fieldset->addField(
            'date_from',
            'date',
            [
                'name' => 'date_from',
                'label' => __('From'),
                'title' => __('From'),
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
                'class' => 'validate-date',
                'required' => true,
                'singleClick' => true
            ]
        );

        $fieldset->addField(
            'date_to',
            'date',
            [
                'name' => 'date_to',
                'label' => __('To'),
                'title' => __('To'),
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
                'class' => 'validate-date',
                'required' => true,
                'singleClick' => true
            ]
        );

        $fieldset->addField(
            'force_sync_flag',
            'select',
            [
                'name' => 'force_sync_flag',
                'label' => __('Force sync'),
                'title' => __('Enable force transaction sync'),
                'values' => [
                    [
                        'value' => 0,
                        'label' => 'No',
                    ],
                    [
                        'value' => 1,
                        'label' => 'Yes',
                    ],
                ],
                'class' => 'select',
                'after_element_html' => '
                    <span class="tooltip-top" style="margin-left: 0.5rem">
                        <a href="#" class="tooltip-toggle">What is this?</a>
                        <span class="tooltip-content">
                            The "Force sync" option will update all selected orders and credit
                            memos in TaxJar regardless of last updated or synced dates.
                        </span>
                    </span>
                ',
            ]
        );

        $form->setAction('#');
        $form->setUseContainer(true);
        $form->setId(self::FORM_ELEMENT_ID);
        $form->setMethod('post');

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
