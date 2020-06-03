<?php
namespace Taxjar\SalesTax\Ui\Component\Listing\Column;

use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

use \Magento\Sales\Api\CreditmemoRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Stdlib\DateTime\Timezone;
use \Magento\Framework\Exception\NoSuchEntityException;

class SyncedCreditmemoOrder extends Column
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var Timezone
     */
    protected $timezone;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    protected $logger;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param Timezone $timezone
     * @param \Taxjar\SalesTax\Model\Logger $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CreditmemoRepositoryInterface $creditmemoRepository,
        Timezone $timezone,
        \Taxjar\SalesTax\Model\Logger $logger,
        array $components = [],
        array $data = []
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->timezone = $timezone;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_DEFAULT_LOG);
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $creditmemoSyncDate = '';

                try {
                    $creditmemo = $this->creditmemoRepository->get($item['entity_id']);

                    if ($creditmemo->getTjSalestaxSyncDate()) {
                        $creditmemoSyncDate = $this->timezone->formatDate(
                            new \DateTime($creditmemo->getTjSalestaxSyncDate()),
                            \IntlDateFormatter::MEDIUM,
                            true
                        );
                    }
                } catch (NoSuchEntityException $e) {
                    $this->logger->log($e->getMessage() . ', entity id: ' . $item['entity_id']);
                }

                $item[$this->getName()] = $creditmemoSyncDate;
            }
        }

        return $dataSource;
    }
}
