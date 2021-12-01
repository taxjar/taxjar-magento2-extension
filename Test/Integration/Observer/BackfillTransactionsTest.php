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

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Integration\Observer;

use Magento\AsynchronousOperations\Model\ResourceModel\Bulk\Collection as BulkCollection;
use Magento\Framework\Event\Observer;
use Taxjar\SalesTax\Observer\BackfillTransactions;
use Taxjar\SalesTax\Test\Integration\IntegrationTestCase;

class BackfillTransactionsTest extends IntegrationTestCase
{
    /**
     * @var BulkCollection
     */
    private $bulkCollection;
    /**
     * @var BackfillTransactions
     */
    private $sut;

    protected function setUp(): void
    {
        $this->objectManager->configure([
            'preferences' => [
                'Taxjar\SalesTax\Model\Logger' =>
                    'Taxjar\SalesTax\Test\Integration\Test\Stubs\LoggerStub'
            ]
        ]);

        $this->bulkCollection = $this->objectManager->get(BulkCollection::class);
        $this->sut = $this->objectManager->get(BackfillTransactions::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/customer_creditmemo_with_two_items.php
     * @magentoConfigFixture default/tax/taxjar/sandbox 0
     * @magentoConfigFixture default/tax/taxjar/apikey valid-api-key
     */
    public function testExecute(): void
    {
        $this->sut->uuid = 'test-uuid';
        $this->sut->execute(new Observer());

        $bulkItems = $this->bulkCollection->addFilter('uuid', 'test-uuid')->getItems();
        $bulkId = array_keys($bulkItems)[0];

        $expectedData = [
            'id' => (string)$bulkId,
            'uuid' => 'test-uuid',
            'user_id' => null,
            'user_type' => '2',
            'description' => 'TaxJar Transaction Sync Backfill',
            'operation_count' => '1',
            'orig_data' => null
        ];

        // only compare array keys defined in expected data
        $actualData = array_intersect_key(
            $bulkItems[$bulkId]->getData(),
            array_flip(array_keys($expectedData))
        );

        $this->assertCount(1, $bulkItems);
        $this->assertEquals($expectedData, $actualData);
    }
}
