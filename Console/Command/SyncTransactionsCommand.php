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

namespace Taxjar\SalesTax\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Taxjar\SalesTax\Model\Logger;

class SyncTransactionsCommand extends Command
{
    private const ARG_START_DATE = '<from>';

    private const ARG_END_DATE = '<to>';

    private const OPTION_FORCE_SYNC = 'force';

    private const OPTION_FORCE_SYNC_SHORTCUT = 'f';

    /**
     * @var \Magento\Framework\App\State
     */
    private State $_state;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $_eventManager;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    private Logger $_logger;

    /**
     * @var string|null
     */
    public ?string $startDate = null;

    /**
     * @var string|null
     */
    public ?string $endDate = null;

    /**
     * @var bool
     */
    public bool $forceSync = false;

    /**
     * @param State $state
     * @param ManagerInterface $eventManager
     * @param Logger $logger
     */
    public function __construct(
        State $state,
        ManagerInterface $eventManager,
        Logger $logger
    ) {
        parent::__construct();
        $this->_state = $state;
        $this->_eventManager = $eventManager;
        $this->_logger = $logger;
    }

    /**
     * Sets config for CLI command
     */
    protected function configure()
    {
        $this->setName('taxjar:transactions:sync')
            ->setDescription('Sync transactions from Magento to TaxJar')
            ->addArgument(self::ARG_START_DATE, InputArgument::OPTIONAL)
            ->addArgument(self::ARG_END_DATE, InputArgument::OPTIONAL)
            ->addOption(self::OPTION_FORCE_SYNC, self::OPTION_FORCE_SYNC_SHORTCUT);
    }

    /**
     * Manually execute transaction sync from command line.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->_state->setAreaCode(\Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
            $this->_logger->console($output);

            $this->_parseInput($input);

            $this->_eventManager->dispatch('taxjar_salestax_backfill_transactions', [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'force_sync' => $this->forceSync,
            ]);

            $message = __('Transaction backfill successfully initiated.');
            $output->writeln(PHP_EOL . $message);
        } catch (\Exception $e) {
            $message = __('Failed to initiate transaction backfill: %1', $e->getMessage());
            $output->writeln(PHP_EOL . '<error>' . $message . '</error>');
        }
    }

    /**
     * Parse input to set arguments ensuring provided date range is contiguous.
     *
     * @param InputInterface $input
     *
     * @return void
     * @throws LocalizedException
     */
    private function _parseInput(InputInterface $input): void
    {
        $this->startDate = $input->getArgument(self::ARG_START_DATE);
        $this->endDate = $input->getArgument(self::ARG_END_DATE);
        $this->forceSync = (bool) $input->getOption(self::OPTION_FORCE_SYNC);

        if ($this->startDate === null || $this->endDate === null) {
            return;
        }

        if (strtotime($this->startDate) > strtotime($this->endDate)) {
            throw new LocalizedException(
                __('Invalid date range provided.')
            );
        }
    }
}
