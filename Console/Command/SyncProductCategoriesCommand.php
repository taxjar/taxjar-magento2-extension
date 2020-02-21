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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Observer\ImportCategories;

class SyncProductCategoriesCommand extends Command
{
    /**
     * @var State
     */
    protected $state;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ImportCategories
     */
    protected $importCategories;

    /**
     * @param State $state
     * @param Logger $logger
     * @param ImportCategories $importCategories
     */
    public function __construct(
        State $state,
        Logger $logger,
        ImportCategories $importCategories
    ) {
        $this->state = $state;
        $this->logger = $logger;
        $this->importCategories = $importCategories;

        parent::__construct();
    }

    /**
     * Sets config for CLI command
     */
    protected function configure()
    {
        $this->setName('taxjar:product_categories:sync')
            ->setDescription('Sync Product Tax Categories from TaxJar to Magento');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return string
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            $this->state->setAreaCode('adminhtml');
            $this->logger->console($output);

            $this->importCategories->execute(new \Magento\Framework\Event\Observer);
            $output->writeln(PHP_EOL . 'Successfully synced product tax categories.');

        } catch (\Exception $e) {
            $output->writeln(PHP_EOL . '<error>Failed to sync product tax categories: ' . $e->getMessage() . '</error>');
        }
    }
}
