<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Integration\Observer;

use Magento\AsynchronousOperations\Model\ResourceModel\Bulk\CollectionFactory as BulkCollectionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Observer\ImportRates;
use Taxjar\SalesTax\Test\Integration\IntegrationTestCase;
use Taxjar\SalesTax\Test\Integration\Test\Stubs\ClientStub;

class ImportRatesTest extends IntegrationTestCase
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    protected function setUp(): void
    {
        parent::setUp();

        Bootstrap::getObjectManager()->configure([
            'preferences' => [
                Client::class => ClientStub::class,
            ]
        ]);

        $this->messageManager = $this->objectManager->get(ManagerInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default/tax/taxjar/apikey test-api-key
     * @magentoConfigFixture default/tax/taxjar/backup 1
     * @magentoConfigFixture default/tax/taxjar/backup_rate_count 2
     * @magentoConfigFixture default/tax/taxjar/customer_tax_classes 2
     * @magentoConfigFixture default/tax/taxjar/product_tax_classes 3,4
     * @magentoConfigFixture default/tax/classes/shipping_tax_class 5
     */
    public function testExecuteCreatesSuccessNotification()
    {
        /** @var ImportRates $sut */
        $sut = $this->objectManager->get(ImportRates::class);
        $sut->client->mockResponse = [
            'rates' => [
                [
                    'state' => 'TX',
                    'zip' => '*',
                    'rate' => 0.0825,
                ],
            ],
        ];

        $sut->execute(new Observer());

        $messages = $this->messageManager->getMessages();

        self::assertEquals(1, $messages->getCount());
        self::assertEquals(
            'TaxJar has successfully queued backup tax rate sync. Thanks for using TaxJar!',
            $messages->getLastAddedMessage()->getText()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default/tax/taxjar/apikey test-api-key
     * @magentoConfigFixture default/tax/taxjar/backup 1
     * @magentoConfigFixture default/tax/taxjar/backup_rate_count 2
     * @magentoConfigFixture default/tax/taxjar/customer_tax_classes 2
     * @magentoConfigFixture default/tax/taxjar/debug 1
     * @magentoConfigFixture default/tax/taxjar/product_tax_classes 3,4
     * @magentoConfigFixture default/tax/classes/shipping_tax_class 5
     */
    public function testExecuteCreatesDebugNotification()
    {
        /** @var ImportRates $sut */
        $sut = $this->objectManager->get(ImportRates::class);
        $sut->client->mockResponse = [
            'rates' => [
                [
                    'state' => 'TX',
                    'zip' => '*',
                    'rate' => 0.0825,
                ],
            ],
        ];

        $sut->execute(new Observer());

        $messages = $this->messageManager->getMessages();

        self::assertEquals(1, $messages->getCount());
        self::assertEquals(
            'Debug mode enabled. Backup tax rates have not been altered.',
            $messages->getLastAddedMessage()->getText()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default/tax/taxjar/apikey test-api-key
     * @magentoConfigFixture default/tax/taxjar/backup 1
     * @magentoConfigFixture default/tax/taxjar/backup_rate_count 2
     * @magentoConfigFixture default/tax/taxjar/customer_tax_classes 2
     * @magentoConfigFixture default/tax/taxjar/product_tax_classes 3,4
     * @magentoConfigFixture default/tax/classes/shipping_tax_class 5
     */
    public function testExecuteCreatesDeleteNotification()
    {
        /** @var ImportRates $sut */
        $sut = $this->objectManager->get(ImportRates::class);
        $sut->client->mockResponse = ['rates' => []];

        $sut->execute(new Observer());

        $messages = $this->messageManager->getMessages();

        self::assertEquals(1, $messages->getCount());
        self::assertEquals(
            'Backup rates imported by TaxJar have been queued for removal.',
            $messages->getLastAddedMessage()->getText()
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default/tax/taxjar/apikey test-api-key
     * @magentoConfigFixture default/tax/taxjar/backup 1
     * @magentoConfigFixture default/tax/taxjar/backup_rate_count 2
     * @magentoConfigFixture default/tax/taxjar/customer_tax_classes 2
     * @magentoConfigFixture default/tax/taxjar/product_tax_classes 3,4
     * @magentoConfigFixture default/tax/classes/shipping_tax_class 5
     */
    public function testExecuteSchedulesBulkOperationForRateCreation()
    {
        /** @var ImportRates $sut */
        $sut = $this->objectManager->get(ImportRates::class);
        $sut->client->mockResponse = [
            'rates' => [
                [
                    'state' => 'TX',
                    'zip' => '*',
                    'rate' => 0.0825,
                ],
            ],
        ];

        $sut->execute(new Observer());

        $bulkCollection = ($this->objectManager->get(BulkCollectionFactory::class))->create();
        $bulkItem = $bulkCollection->getFirstItem();

        self::assertEquals(1, count($bulkCollection->getItems()));
        self::assertEquals('Create TaxJar backup tax rates.', $bulkItem->getData('description'));
    }
}
