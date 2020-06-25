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

namespace Taxjar\SalesTax\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Logger
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $driverFile;

    /**
     * @var array
     */
    protected $playback = [];

    /**
     * @var bool
     */
    protected $isRecording;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $console;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string
     */
    protected $filename = TaxjarConfig::TAXJAR_DEFAULT_LOG;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @var boolean
     */
    protected $isForced = false;

    /**
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $driverFile
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $driverFile,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        TaxjarConfig $taxjarConfig
    ) {
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->taxjarConfig = $taxjarConfig;
    }

    /**
     * Sets the log filename
     *
     * @param string $filename
     * @return Logger
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Enables or disables the logger
     *
     * @param boolean $isForced
     * @return Logger
     */
    public function force($isForced = true)
    {
        $this->isForced = $isForced;
        return $this;
    }

    /**
     * Get the temp log filename
     *
     * @return string
     */
    public function getPath()
    {
        return $this->directoryList->getPath(DirectoryList::LOG) . DIRECTORY_SEPARATOR . 'taxjar' . DIRECTORY_SEPARATOR . $this->filename;
    }

    /**
     * Save a message to taxjar.log
     *
     * @param string $message
     * @param string $label
     * @throws LocalizedException
     * @return void
     */
    public function log($message, $label = '')
    {
        if ($this->scopeConfig->getValue(
            TaxjarConfig::TAXJAR_DEBUG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId())
            ||
            $this->isForced
        ) {
            try {
                if (!empty($label)) {
                    $label = '[' . strtoupper($label) . '] ';
                }

                if ($this->taxjarConfig->isSandboxEnabled()) {
                    $label = '[SANDBOX] ' . $label;
                }

                $timestamp = date('d M Y H:i:s', time());
                $message = sprintf('%s%s - %s%s', PHP_EOL, $timestamp, $label, $message);

                if (!is_dir(dirname($this->getPath()))) {
                    // dir doesn't exist, make it
                    mkdir(dirname($this->getPath()));
                }

                $this->driverFile->filePutContents($this->getPath(), $message, FILE_APPEND);

                if ($this->isRecording) {
                    $this->playback[] = $message;
                }
                if ($this->console) {
                    $this->console->write($message);
                }
            } catch (\Exception $e) {
                // @codingStandardsIgnoreStart
                throw new LocalizedException(__('Could not write to your Magento log directory under /var/log. Please make sure the directory is created and check permissions for %1.', $this->directoryList->getPath('log')));
                // @codingStandardsIgnoreEnd
            }
        }
    }

    /**
     * Enable log recording
     *
     * @return void
     */
    public function record()
    {
        $this->isRecording = true;
    }

    /**
     * Return log recording
     *
     * @return array
     */
    public function playback()
    {
        return $this->playback;
    }

    /**
     * Set console output interface
     *
     * @return void
     */
    public function console($output)
    {
        $this->console = $output;
    }
}
