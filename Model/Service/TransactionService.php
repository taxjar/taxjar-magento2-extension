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

namespace Taxjar\SalesTax\Model\Service;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Taxjar\SalesTax\Api\Data\TransactionInterface;
use Taxjar\SalesTax\Api\Data\TransactionManagementInterface;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\Configuration;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Transaction\OrderFactory;
use Taxjar\SalesTax\Model\Transaction\RefundFactory;

/**
 * Transaction service provides module's ability to sync Sales Orders or Creditmemos to TaxJar.
 */
class TransactionService implements TransactionManagementInterface
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private \Magento\Framework\Event\ManagerInterface $_eventManager;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    private \Taxjar\SalesTax\Model\Logger $_logger;

    /**
     * @var \Taxjar\SalesTax\Model\Client
     */
    private \Taxjar\SalesTax\Model\Client $_client;

    /**
     * @var Configuration
     */
    private Configuration $_configuration;

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\OrderFactory
     */
    private \Taxjar\SalesTax\Model\Transaction\OrderFactory $_order;

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\RefundFactory
     */
    private \Taxjar\SalesTax\Model\Transaction\RefundFactory $_refund;

    /**
     * Transaction Service constructor.
     *
     * @param ManagerInterface $eventManager
     * @param Logger $logger
     * @param Client $client
     * @param Configuration $configuration
     * @param OrderFactory $order
     * @param RefundFactory $refund
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Taxjar\SalesTax\Model\Client $client,
        \Taxjar\SalesTax\Model\Configuration $configuration,
        \Taxjar\SalesTax\Model\Transaction\OrderFactory $order,
        \Taxjar\SalesTax\Model\Transaction\RefundFactory $refund
    ) {
        $this->_eventManager = $eventManager;
        $this->_logger = $logger;
        $this->_client = $client;
        $this->_configuration = $configuration;
        $this->_order = $order;
        $this->_refund = $refund;
    }

    /**
     * @inheritDoc
     */
    public function sync(CreditmemoInterface|OrderInterface $transaction, bool $force = false): bool
    {
        $this->_eventManager->dispatch('taxjar_salestax_transaction_sync_before', [
            'transaction' => $transaction,
            'force_sync' => $force,
        ]);

        $result = $this->_syncTransaction($transaction, $force);

        $this->_eventManager->dispatch('taxjar_salestax_transaction_sync_after', [
            'transaction' => $transaction,
            'force_sync' => $force,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Attempt to sync an order or creditmemo with TaxJar.
     *
     * @param CreditmemoInterface|OrderInterface $transaction
     * @param bool $force
     *
     * @return bool
     * @throws LocalizedException
     */
    private function _syncTransaction(CreditmemoInterface|OrderInterface $transaction, bool $force = false): bool
    {
        $synced = false;

        try {
            if ($transaction->getEntityId() !== null) {
                $this->_setApiKey($transaction->getStoreId());
                $transaction = $this->_getTransaction($transaction);
                if ($transaction->canSync() && $transaction->shouldSync($force)) {
                    $response = $this->_makeRequest($transaction, $transaction->hasSynced());
                    $message = $this->_getResponseMessage($transaction, $response);
                    $transaction->setLastSyncDate(gmdate('Y-m-d H:i:s'));
                    $label = 'api';
                    $synced = true;
                } else {
                    $message = $this->_getSkippedMessage($transaction);
                    $label = 'skip';
                }
            }
        } catch (LocalizedException $e) {
            $message = $e->getMessage();
            $label = 'error';
        } catch (\Error $e) {
            $message = $e->getMessage();
            $label = 'error';
        } finally {
            if (isset($message) && isset($label)) {
                $this->_logger->log($message, $label);
            }
            return $synced;
        }
    }

    /**
     * Set API key based on store.
     *
     * @param int $storeId
     *
     * @return void
     * @throws LocalizedException
     */
    private function _setApiKey(int $storeId): void
    {
        $apiKey = $this->_configuration->getApiKey($storeId);
        if ($apiKey === '') {
            throw new LocalizedException(
                __('No TaxJar API key is configured for the current scope.')
            );
        }
        $this->_client->setApiKey($apiKey);
    }

    /**
     * Initialize tax transaction from order or creditmemo object.
     *
     * @param CreditmemoInterface|OrderInterface $transaction
     *
     * @return TransactionInterface
     * @throws LocalizedException
     */
    private function _getTransaction(CreditmemoInterface|OrderInterface $transaction): TransactionInterface
    {
        switch ($transaction) {
            case ($transaction instanceof OrderInterface):
                return $this->_order->create(['transaction' => $transaction]);
            case ($transaction instanceof CreditmemoInterface):
                return $this->_refund->create(['transaction' => $transaction]);
            default:
                throw new LocalizedException(
                    __('Unhandled object type encountered in transaction sync.')
                );
        }
    }

    /**
     * Make POST or PUT request.
     *
     * @param TransactionInterface $transaction
     * @param bool $hasPrevSynced
     *
     * @return array
     * @throws LocalizedException
     */
    private function _makeRequest(TransactionInterface $transaction, bool $hasPrevSynced = false): array
    {
        $method = $hasPrevSynced ? 'PUT' : 'POST';
        $args = $this->_getParams($method, $transaction);
        $message = $this->_getRequestMessage($transaction);

        $this->_logger->log($message, $method);

        try {
            return $this->_clientRequest($args, $hasPrevSynced);
        } catch (LocalizedException $e) {
            if ($this->_isRecoverable($e, $hasPrevSynced)) {
                $this->_logger->log($this->_getRetryMessage($transaction), 'retry');
                return $this->_makeRequest($transaction, !$hasPrevSynced);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Send client POST or PUT request depending on if resource should already exist in TaxJar.
     *
     * @param array $args
     * @param bool $hasPrevSynced
     *
     * @return array
     * @throws LocalizedException Client API methods are not annotated with @throws, but LocalizedException may occur
     */
    private function _clientRequest(array $args, bool $hasPrevSynced): array
    {
        if ($hasPrevSynced === true) {
            return $this->_client->putResource(...array_values($args));
        } else {
            return $this->_client->postResource(...array_values($args));
        }
    }

    /**
     * Determines if exception is due to:
     * 1) Attempted API update (PUT) of a non-existent TaxJar transaction record
     * 2) Attempted API creation (POST) of a transaction record that already exists in TaxJar
     *
     * @param LocalizedException $e
     * @param bool $hasPrevSynced
     *
     * @return bool
     */
    private function _isRecoverable(LocalizedException $e, bool $hasPrevSynced): bool
    {
        return ($hasPrevSynced && $e->getCode() === 404) || (! $hasPrevSynced && $e->getCode() === 422);
    }

    /**
     * Get request parameters based on object and request type.
     *
     * @param string $method
     * @param TransactionInterface $transaction
     *
     * @return array
     * @throws LocalizedException
     *
     * @see Client::putResource()
     * @see Client::postResource()
     */
    private function _getParams(string $method, TransactionInterface $transaction): array
    {
        if ($method === 'POST') {
            return [
                'resource' => $transaction->getResourceName(),
                'request' => $transaction->getRequestBody(),
            ];
        } elseif ($method === 'PUT') {
            return [
                'resource' => $transaction->getResourceName(),
                'id' => $transaction->getResourceId(),
                'request' => $transaction->getRequestBody(),
            ];
        } else {
            return [];
        }
    }

    /**
     * Get request log message based on Transaction.
     *
     * @param TransactionInterface $transaction
     *
     * @return Phrase
     * @throws LocalizedException
     */
    private function _getRequestMessage(TransactionInterface $transaction): Phrase
    {
        return __(
            'Pushing %1 %2: %3',
            $transaction->getResourceName(),
            $transaction->getResourceId(),
            json_encode($transaction->getRequestBody())
        );
    }

    /**
     * Get response log message based on Transaction and response body.
     *
     * @param TransactionInterface $transaction
     * @param array $response
     *
     * @return Phrase
     */
    private function _getResponseMessage(
        TransactionInterface $transaction,
        array $response
    ): Phrase {
        return __(
            '%1 #%2 saved to TaxJar: %3',
            ucfirst($transaction->getResourceName()),
            $transaction->getResourceId(),
            json_encode($response)
        );
    }

    /**
     * Get retry log message based on Transaction data.
     *
     * @param TransactionInterface $transaction
     *
     * @return Phrase
     */
    private function _getRetryMessage(TransactionInterface $transaction): Phrase
    {
        return __(
            'Attempting to retry saving %1 #%2.',
            $transaction->getResourceName(),
            $transaction->getResourceId()
        );
    }

    /**
     * Get skipped log message based on Transaction data.
     *
     * @param TransactionInterface $transaction
     *
     * @return Phrase
     */
    private function _getSkippedMessage(TransactionInterface $transaction): Phrase
    {
        return __(
            '%1 #%2 not eligible for sync or not updated since last sync.',
            ucfirst($transaction->getResourceName()),
            $transaction->getResourceId()
        );
    }
}
