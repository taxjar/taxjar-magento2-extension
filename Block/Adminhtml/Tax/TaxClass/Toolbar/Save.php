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

/**
 * Admin tax class save toolbar
 */
namespace Taxjar\SalesTax\Block\Adminhtml\Tax\TaxClass\Toolbar;

class Save extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\ContainerInterface
{
    /**
     * @var \Magento\Backend\Block\Widget\Button\ButtonList
     */
    protected $buttonList;

    /**
     * @var \Magento\Backend\Block\Widget\Button\ToolbarInterface
     */
    protected $toolbar;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
     * @param \Magento\Backend\Block\Widget\Button\ToolbarInterface $toolbar
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList,
        \Magento\Backend\Block\Widget\Button\ToolbarInterface $toolbar,
        array $data = []
    ) {
        $this->buttonList = $buttonList;
        $this->toolbar = $toolbar;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->assign('createUrl', $this->getUrl('taxjar/taxClass/save'));
    }

    /**
     * Public wrapper for the button list
     *
     * @param string $buttonId
     * @param array $data
     * @param integer $level
     * @param integer $sortOrder
     * @param string|null $region That button should be displayed in ('toolbar', 'header', 'footer', null)
     * @return $this
     */
    public function addButton($buttonId, $data, $level = 0, $sortOrder = 0, $region = 'toolbar')
    {
        $this->buttonList->add($buttonId, $data, $level, $sortOrder, $region);
        return $this;
    }

    /**
     * Public wrapper for the button list
     *
     * @param string $buttonId
     * @return $this
     */
    public function removeButton($buttonId)
    {
        $this->buttonList->remove($buttonId);
        return $this;
    }

    /**
     * Public wrapper for protected _updateButton method
     *
     * @param string $buttonId
     * @param string|null $key
     * @param string $data
     * @return $this
     */
    public function updateButton($buttonId, $key, $data)
    {
        $this->buttonList->update($buttonId, $key, $data);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => 'window.location.href=\'' . $this->getUrl('taxjar/*/') . '\'',
                'class' => 'back'
            ]
        );

        $this->buttonList->add(
            'reset',
            ['label' => __('Reset'), 'onclick' => 'window.location.reload()', 'class' => 'reset']
        );

        $class = intval($this->getRequest()->getParam('class'));
        if ($class) {
            $this->buttonList->add(
                'delete',
                [
                    'label' => __('Delete Product Tax Class'),
                    'onclick' => 'deleteConfirm(\'' . __(
                        'Are you sure you want to do this?'
                    ) . '\', \'' . $this->getUrl(
                        'taxjar/*/delete',
                        ['class' => $class]
                    ) . '\')',
                    'class' => 'delete'
                ]
            );
        }

        $this->buttonList->add(
            'save',
            [
                'label' => __('Save Product Tax Class'),
                'class' => 'save primary save-tax-class',
                'onclick' => 'jQuery("#taxClassForm").submit()'
            ]
        );
        $this->toolbar->pushButtons($this, $this->buttonList);
        return parent::_prepareLayout();
    }

    /**
     * Check whether button rendering is allowed in current context
     *
     * @param \Magento\Backend\Block\Widget\Button\Item $item
     * @return bool
     */
    public function canRender(\Magento\Backend\Block\Widget\Button\Item $item)
    {
        return !$item->isDeleted();
    }
}
