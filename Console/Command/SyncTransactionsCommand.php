<?php

namespace Taxjar\SalesTax\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Event\Manager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Observer\BackfillTransactions;

class SyncTransactionsCommand extends Command
{
    const FROM_ARGUMENT = '<from>';
    const TO_ARGUMENT = '<to>';
    const OPTION_FORCE = 'force';
    const OPTION_FORCE_SHORT = 'f';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|Manager
     */
    protected $eventManager;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    protected $logger;

    /**
     * @var \Taxjar\SalesTax\Observer\BackfillTransactions
     */
    protected $backfillTransactions;

    /**
     * @param State $state
     * @param ManagerInterface|Manager $eventManager
     * @param Logger $logger
     * @param BackfillTransactions $backfillTransactions
     */
    public function __construct(
        State $state,
        Manager $eventManager,
        Logger $logger,
        BackfillTransactions $backfillTransactions
    ) {
        $this->state = $state;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->backfillTransactions = $backfillTransactions;
        parent::__construct();
    }

    /**
     * Sets config for CLI command
     */
    protected function configure()
    {
        $this->setName('taxjar:transactions:sync')
            ->setDescription('Sync transactions from Magento to TaxJar')
            ->addArgument(self::FROM_ARGUMENT, InputArgument::OPTIONAL)
            ->addArgument(self::TO_ARGUMENT, InputArgument::OPTIONAL)
            ->addOption(self::OPTION_FORCE, self::OPTION_FORCE_SHORT);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            $this->state->setAreaCode('adminhtml');
            $this->logger->console($output);
            $this->backfillTransactions->execute(new Observer([
                'from_date' => $input->getArgument(self::FROM_ARGUMENT),
                'to_date' => $input->getArgument(self::TO_ARGUMENT),
                'force' => (bool) $input->getOption(self::OPTION_FORCE)
            ]));
        } catch (\Exception $e) {
            $output->writeln(PHP_EOL . '<error>Failed to sync transactions: ' . $e->getMessage() . '</error>');
        }
    }
}
