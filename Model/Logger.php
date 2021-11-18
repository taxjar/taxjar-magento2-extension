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

namespace Taxjar\SalesTax\Model;

class Logger
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\DriverInterface
     */
    protected $fileDriver;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Taxjar\SalesTax\Model\Configuration
     */
    protected $taxjarConfig;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    protected $console;

    /**
     * @var string
     */
    protected $filename = \Taxjar\SalesTax\Model\Configuration::TAXJAR_DEFAULT_LOG;

    /**
     * @var bool
     */
    protected $isForced = false;

    /**
     * @var bool
     */
    protected $isRecording;

    /**
     * @var array
     */
    protected $playback = [];

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\DriverInterface $fileDriver
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Taxjar\SalesTax\Model\Configuration $taxjarConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\DriverInterface $fileDriver,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Taxjar\SalesTax\Model\Configuration $taxjarConfig
    ) {
        $this->directoryList = $directoryList;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->fileDriver = $fileDriver;
        $this->taxjarConfig = $taxjarConfig;
    }

    /**
     * Sets the log filename
     *
     * @param string $filename
     * @return Logger
     */
    public function setFilename(string $filename): Logger
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Manually set the playback value
     *
     * @param string[] $playback
     * @return Logger
     */
    public function setPlayback(array $playback): Logger
    {
        $this->playback = $playback;
        return $this;
    }

    /**
     * Enables or disables the logger
     *
     * @param boolean $isForced
     * @return Logger
     */
    public function force(bool $isForced = true): Logger
    {
        $this->isForced = $isForced;
        return $this;
    }

    /**
     * Get the temp log filename
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getPath(): string
    {
        return sprintf(
            '%s/taxjar/%s',
            $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::LOG),
            $this->filename
        );
    }

    /**
     * Save a message to taxjar.log
     *
     * @param string|mixed $message
     * @param mixed|null $label
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function log($message, $label = ''): void
    {
        if ($this->scopeConfig->getValue(
            \Taxjar\SalesTax\Model\Configuration::TAXJAR_DEBUG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        ) || $this->isForced) {
            try {
                if (!empty($label)) {
                    $label = '[' . strtoupper($label) . '] ';
                }

                if ($this->taxjarConfig->isSandboxEnabled()) {
                    $label = '[SANDBOX] ' . $label;
                }

                $timestamp = date('d M Y H:i:s', time());
                $message = sprintf('%s%s - %s%s', PHP_EOL, $timestamp, $label, $message);

                $path = $this->getPath();
                $dirname = $this->fileDriver->getParentDirectory($path);

                if (!$this->fileDriver->isDirectory($dirname)) {
                    // dir doesn't exist, make it
                    $this->fileDriver->createDirectory($dirname);
                }

                $this->fileDriver->filePutContents($this->getPath(), $message, FILE_APPEND);

                if ($this->isRecording) {
                    $this->playback[] = $message;
                }
                if ($this->console) {
                    $this->console->write($message);
                }
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __(
                        'Could not write to your Magento log directory under /var/log. ' .
                        'Please make sure the directory is created and check permissions for %1.',
                        $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::LOG)
                    )
                );
            }
        }
    }

    /**
     * Enable log recording
     *
     * @return void
     */
    public function record(): void
    {
        $this->isRecording = true;
    }

    /**
     * Return log recording
     *
     * @return array
     */
    public function playback(): array
    {
        return $this->playback;
    }

    /**
     * Set console output interface
     *
     * @param $output
     * @return void
     */
    public function console($output): void
    {
        $this->console = $output;
    }
}
