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

namespace Taxjar\SalesTax\Model;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Shipping\Model\Config;
use Taxjar\SalesTax\Api\Data\TransactionInterface;

abstract class Transaction implements TransactionInterface
{
    public const FIELD_SYNC_DATE = 'tj_salestax_sync_date';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $_scopeConfig;

    /**
     * @var RegionFactory
     */
    private RegionFactory $_regionFactory;

    /**
     * @var Logger
     */
    private Logger $_logger;

    /**
     * @var CreditmemoInterface|OrderInterface
     */
    protected $_transaction;

    /**
     * @var \Magento\Sales\Api\Data\OrderAddressInterface|null
     */
    protected $_transactionAddress;

    /**
     * Transaction constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param RegionFactory $regionFactory
     * @param Logger $logger
     * @param mixed $transaction
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RegionFactory $regionFactory,
        Logger $logger,
        $transaction
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_regionFactory = $regionFactory;
        $this->_logger = $logger;
        $this->_transaction = $transaction;
    }

    /**
     * @inheritDoc
     */
    abstract public function canSync(): bool;

    /**
     * @inheritDoc
     */
    abstract public function shouldSync(bool $force = false): bool;

    /**
     * @inheritDoc
     */
    abstract public function getRequestBody(): array;

    /**
     * Return API request body's representation of transaction line items.
     *
     * @return array
     */
    abstract protected function getLineItemData(): array;

    /**
     * @inheritDoc
     */
    public function getResourceId(): ?string
    {
        return $this->_transaction->getEntityId();
    }

    /**
     * Check if a transaction is synced
     *
     * @return bool
     */
    public function hasSynced(): bool
    {
        $syncDate = $this->_transaction->getData(self::FIELD_SYNC_DATE);
        return ! (empty($syncDate) || $syncDate == '0000-00-00 00:00:00');
    }

    /**
     * Get API provider string.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProvider(): string
    {
        return $this->getExternalProvider() ?: 'api';
    }

    /**
     * Get API provider for externally imported transactions.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getExternalProvider()
    {
        try {
            if (class_exists('\Ess\M2ePro\Model\Order')) {
                $m2eOrder = ObjectManager::getInstance()->create('\Ess\M2ePro\Model\Order');
                $m2eOrder = $m2eOrder->load($this->_transaction->getId(), 'magento_order_id');
                if (in_array($m2eOrder->getComponentMode(), ['amazon', 'ebay', 'walmart'])) {
                    return $m2eOrder->getComponentMode();
                }
            }
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            $this->_logger->log('M2e order does not exist or component mode can\'t be loaded');
        }

        return null;
    }

    /**
     * Return origin address from store configuration.
     *
     * @return array
     */
    protected function getAddressFrom(): array
    {
        $fromAddress = [
            'from_country' => Config::XML_PATH_ORIGIN_COUNTRY_ID,
            'from_zip' => Config::XML_PATH_ORIGIN_POSTCODE,
            'from_state' => Config::XML_PATH_ORIGIN_REGION_ID,
            'from_city' => Config::XML_PATH_ORIGIN_CITY,
            'from_street' => 'shipping/origin/street_line1'
        ];

        foreach ($fromAddress as $key => $path) {
            $value = $this->_getStoreValue($path, $this->_transaction->getStoreId());

            if ($key === 'from_state') {
                $value = $this->_regionFactory->create()->load($value)->getCode();
            }

            $fromAddress[$key] = $value;
        }

        return $fromAddress;
    }

    /**
     * Return origin address from store configuration.
     *
     * @return array
     */
    protected function getAddressTo(): array
    {
        if ($this->_getOrderAddress() !== null) {
            return [
                'to_country' => $this->_getOrderAddress()->getCountryId(),
                'to_zip' => $this->_getOrderAddress()->getPostcode(),
                'to_state' => $this->_getOrderAddress()->getRegionCode(),
                'to_city' => $this->_getOrderAddress()->getCity(),
                'to_street' => $this->_getOrderAddress()->getStreetLine(1)
            ];
        }

        return [];
    }

    /**
     * Get optional customer exemption address from transaction object.
     *
     * @return array
     */
    protected function getCustomerExemption(): array
    {
        $customerId = $this->_getOrder()->getCustomerId();

        if ($customerId !== null) {
            return ['customer_id' => $customerId];
        }

        return [];
    }

    /**
     * Get destination address from transaction object.
     *
     * @return \Magento\Sales\Api\Data\OrderAddressInterface|null
     */
    protected function _getOrderAddress():  ?\Magento\Sales\Api\Data\OrderAddressInterface
    {
        if ($this->_transactionAddress !== null) {
            return $this->_transactionAddress;
        }

        $this->_transactionAddress = $this->_getOrder()->getIsVirtual()
            ? $this->_getOrder()->getBillingAddress()
            : $this->_getOrder()->getShippingAddress();

        return $this->_transactionAddress;
    }

    /**
     * Return the Magento Sales Order related to the API transaction.
     *
     * @return OrderInterface|null
     */
    private function _getOrder(): ?\Magento\Sales\Api\Data\OrderInterface
    {
        if ($this->_transaction instanceof CreditmemoInterface) {
            return $this->_transaction->getOrder();
        } elseif ($this->_transaction instanceof OrderInterface) {
            return $this->_transaction;
        } else {
            return null;
        }
    }

    /**
     * Retrieve a store-scoped core config value.
     *
     * @param string $path
     * @param int|string|null $storeId
     *
     * @return mixed
     */
    protected function _getStoreValue(string $path, $storeId = null)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function setLastSyncDate($datetime): void
    {
        $this->_transaction->setData('tj_salestax_sync_date', $datetime);
    }
}
