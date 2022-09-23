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
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Model\Transaction;

use DateTime;
use Exception;
use Magento\Catalog\Model\ProductRepository;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\AbstractModel;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataRepositoryInterface;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataSearchResultInterface;
use Taxjar\SalesTax\Helper\Data as TaxjarHelper;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory;
use Taxjar\SalesTax\Model\Sales\Order\Metadata;

class Order extends \Taxjar\SalesTax\Model\Transaction
{
    protected const SYNCABLE_STATES = ['complete', 'closed'];

    protected const SYNCABLE_CURRENCIES = ['USD'];

    protected const SYNCABLE_COUNTRIES = ['US'];

    /**
     * @var OrderInterface|AbstractModel
     */
    protected $originalOrder;

    /**
     * @var array
     */
    protected $request;

    /**
     * @var MetadataRepositoryInterface
     */
    private MetadataRepositoryInterface $metadataRepository;

    /**
     * @var MetadataSearchResultInterface
     */
    private MetadataSearchResultInterface $metadataSearchResult;

    /**
     * @var \Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory
     */
    private \Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory $collectionFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ClientFactory $clientFactory
     * @param ProductRepository $productRepository
     * @param RegionFactory $regionFactory
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param Logger $logger
     * @param ObjectManagerInterface $objectManager
     * @param TaxjarHelper $helper
     * @param Configuration $taxjarConfig
     * @param MetadataRepositoryInterface $metadataRepository
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ClientFactory $clientFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        Logger $logger,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        TaxjarHelper $helper,
        Configuration $taxjarConfig,
        MetadataRepositoryInterface $metadataRepository,
        \Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory $collectionFactory
    ) {
        $this->metadataRepository = $metadataRepository;
        $this->collectionFactory = $collectionFactory;
        parent::__construct(
            $scopeConfig,
            $clientFactory,
            $productRepository,
            $regionFactory,
            $taxClassRepository,
            $logger,
            $objectManager,
            $helper,
            $taxjarConfig
        );
    }

    /**
     * Set request value
     *
     * @param array $value
     * @return $this
     */
    public function setRequest($value)
    {
        $this->request = $value;

        return $this;
    }

    /**
     * Build an order transaction
     *
     * @param OrderInterface $order
     * @return array
     * @throws Exception
     */
    public function build(OrderInterface $order): array
    {
        $createdAt = new DateTime($order->getCreatedAt());
        $subtotal = (float) $order->getSubtotalInvoiced();
        $shipping = (float) $order->getShippingInvoiced();
        $discount = (float) $order->getDiscountInvoiced();
        $shippingDiscount = (float) $order->getShippingDiscountAmount();
        $salesTax = (float) $order->getTaxInvoiced();

        $this->originalOrder = $order;

        $newOrder = [
            'plugin' => 'magento',
            'provider' => $this->getProvider($order),
            'transaction_id' => $order->getIncrementId(),
            'transaction_date' => $createdAt->format(DateTime::ISO8601),
            'amount' => $subtotal + $shipping - abs($discount),
            'shipping' => $shipping - abs($shippingDiscount),
            'sales_tax' => $salesTax
        ];

        $requestBody = array_merge(
            $newOrder,
            $this->buildFromAddress($order),
            $this->buildToAddress($order),
            $this->buildLineItems($order, $order->getAllItems()),
            $this->buildCustomerExemption($order)
        );

        $this->setRequest($requestBody);

        return $this->request;
    }

    /**
     * @param bool $forceFlag
     * @param string|null $method
     * @throws LocalizedException
     */
    public function push(bool $forceFlag = false, string $method = null)
    {
        $orderUpdatedAt = $this->originalOrder->getUpdatedAt();
        $orderSyncedAt = ($this->originalOrder->getExtensionAttributes() !== null)
            ? $this->originalOrder->getExtensionAttributes()->getTjSyncedAt()
            : $this->originalOrder->getData('tj_salestax_sync_date');

        if ($this->apiKey = $this->taxjarConfig->getApiKey($this->originalOrder->getStoreId())) {
            $this->client->setApiKey($this->apiKey);
        }

        if ($orderUpdatedAt <= $orderSyncedAt) {
            if ($forceFlag) {
                $this->logger->log('Forced update of Order #' . $this->request['transaction_id'], 'api');
            } else {
                $this->logger->log(
                    'Order #' . $this->request['transaction_id'] . ' not updated since last sync',
                    'skip'
                );
                return;
            }
        }

        $httpMethod = $method ?: ($this->isSynced($orderSyncedAt) ? 'PUT' : 'POST');

        try {
            $this->logger->log(
                sprintf(
                    'Pushing order #%s: %s',
                    $this->request['transaction_id'],
                    json_encode($this->request)
                ),
                $httpMethod
            );

            $response = $this->makeRequest($httpMethod);

            $this->logger->log(
                sprintf(
                    'Order #%s saved to TaxJar: %s',
                    $this->request['transaction_id'],
                    json_encode($response)
                ),
                'api'
            );

            $syncDate = gmdate('Y-m-d H:i:s');

            /** @var Metadata $metadata */
            $metadata = $this->collectionFactory->create()
                ->addFieldToFilter('order_id', $this->originalOrder->getId())
                ->getFirstItem();

            $metadata->setSyncedAt($syncDate);

            if (! $metadata->getId()) {
                $metadata->setOrderId($this->originalOrder->getId());
            }

            $this->metadataRepository->save($metadata);

            $extensionAttributes = $this->originalOrder->getExtensionAttributes();
            $extensionAttributes->setTjSyncedAt($syncDate);

            $this->originalOrder->setExtensionAttributes($extensionAttributes);
        } catch (LocalizedException $e) {
            $this->logger->log('Error: ' . $e->getMessage(), 'error');
            $error = json_decode($e->getMessage());
            if ($error && !$method) {
                $this->handleError($error, $httpMethod, $forceFlag);
            }
        }
    }

    /**
     * @param string $method
     * @return array
     * @throws LocalizedException
     */
    protected function makeRequest(string $method): array
    {
        switch ($method) {
            case 'POST':
                return $this->client->postResource('orders', $this->request);
            case 'PUT':
                return $this->client->putResource('orders', $this->request['transaction_id'], $this->request);
            default:
                throw new LocalizedException(
                    __('Unhandled HTTP method "%s" in TaxJar order transaction sync.', $method)
                );
        }
    }

    /**
     * @param $error
     * @param string $method
     * @param bool $forceFlag
     * @throws LocalizedException
     */
    protected function handleError($error, string $method, bool $forceFlag): void
    {
        if ($method == 'POST' && $error->status == 422) {
            $retry = 'PUT';
        }

        if ($method == 'PUT' && $error->status == 404) {
            $retry = 'POST';
        }

        if (isset($retry)) {
            $this->logger->log(
                sprintf('Attempting to retry saving order #%s', $this->request['transaction_id']),
                'retry'
            );
            $this->push($forceFlag, $retry);
        }
    }

    /**
     * Determines if an order can be synced
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function isSyncable(OrderInterface $order): bool
    {
        return $this->stateIsSyncable($order)
            && $this->currencyIsSyncable($order)
            && $this->countryIsSyncable($order);
    }

    protected function stateIsSyncable(OrderInterface $order): bool
    {
        return in_array($order->getState(), self::SYNCABLE_STATES);
    }

    protected function currencyIsSyncable(OrderInterface $order): bool
    {
        return in_array($order->getOrderCurrencyCode(), self::SYNCABLE_CURRENCIES);
    }

    protected function countryIsSyncable(OrderInterface $order): bool
    {
        $address = $order->getIsVirtual() ? $order->getBillingAddress() : $order->getShippingAddress();
        return in_array($address->getCountryId(), self::SYNCABLE_COUNTRIES);
    }
}
