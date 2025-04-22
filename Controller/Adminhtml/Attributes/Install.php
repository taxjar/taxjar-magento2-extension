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
 * @copyright  Copyright (c) 2025 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Controller\Adminhtml\Attributes;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Taxjar\SalesTax\Setup\Patch\Data\AddExtensionAttributesPatch;

class Install extends Action
{
    /**
     * @var AddExtensionAttributesPatch
     */
    protected $attributePatch;

    /**
     * @param Context $context
     * @param AddExtensionAttributesPatch $attributePatch
     */
    public function __construct(
        Context $context,
        AddExtensionAttributesPatch $attributePatch
    ) {
        parent::__construct($context);
        $this->attributePatch = $attributePatch;
    }

    /**
     * Manual install of TaxJar customer attributes
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            // Apply the data patch to install TaxJar attributes
            $this->attributePatch->apply();

            // Clear all caches to ensure the attribute check is updated
            $cacheManager = $this->_objectManager->get(\Magento\Framework\App\Cache\Manager::class);
            $cacheManager->clean(['config', 'layout', 'block_html', 'full_page', 'collections', 'eav']);

            // Force EAV config reload
            $this->_objectManager->get(\Magento\Eav\Model\Config::class)->clear();

            $this->messageManager->addSuccessMessage(
                __(
                    'TaxJar customer attributes have been successfully installed. ' .
                    'You may now configure customer tax exemptions.'
                )
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __(
                    'An error occurred while installing TaxJar customer attributes: %1',
                    $e->getMessage()
                )
            );
        }

        // Redirect back to the customer grid or edit page
        $refererUrl = $this->_redirect->getRefererUrl();
        $resultRedirect->setUrl($refererUrl);

        return $resultRedirect;
    }

    /**
     * Check admin permissions
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Taxjar_SalesTax::config');
    }
}
