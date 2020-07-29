<?php
/**
MIT License

Copyright (c) 2018 Fast Division

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
**/

namespace Taxjar\SalesTax\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Module\ModuleList\Loader as ModuleLoader;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * PluginDx Report Builder
 */
class Plugindx
{
    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\Module\ModuleList\Loader
     */
    protected $moduleLoader;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $moduleResource;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $resolver;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Array
     */
    private $report;

    public function __construct(
        DirectoryList $directoryList,
        ManagerInterface $eventManager,
        ModuleLoader $moduleLoader,
        ObjectManagerInterface $objectManager,
        ProductMetadata $productMetadata,
        Resolver $resolver,
        ResourceInterface $moduleResource,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->directoryList = $directoryList;
        $this->eventManager = $eventManager;
        $this->moduleLoader = $moduleLoader;
        $this->objectManager = $objectManager;
        $this->productMetadata = $productMetadata;
        $this->resolver = $resolver;
        $this->moduleResource = $moduleResource;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function build($config)
    {
        if (is_array($config)) {
            $this->report = $config;
        } else {
            $this->report = json_decode($config, true);
        }

        $this->getConfig();
        $this->getCollections();
        $this->getHelpers();
        $this->getServerInfo();
        $this->getLogs();
        $this->getExtra();

        return json_encode($this->report);
    }

    private function getConfig()
    {
        if (!isset($this->report['config'])) {
            return;
        }

        $configFields = $this->report['config'];

        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                foreach ($group->getStores() as $store) {
                    foreach ($configFields as $fieldIndex => $field) {
                        $storeCode = $store->getCode();
                        $storeName = $store->getName();

                        if (isset($field['path'])) {
                            $value = $this->scopeConfig->getValue(
                                $field['path'],
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $store->getId()
                            );

                            $this->report['config'][$fieldIndex]['values'][$storeCode]['store'] = $storeName;
                            $this->report['config'][$fieldIndex]['values'][$storeCode]['value'] = $value;
                        }
                    }
                }
            }
        }

        foreach ($configFields as $fieldIndex => $field) {
            if (isset($field['type'])) {
                $this->report['config'][$fieldIndex]['type'] = $field['type'];
            }
        }
    }

    private function getCollections()
    {
        if (!isset($this->report['collections'])) {
            return;
        }

        $collections = $this->report['collections'];

        foreach ($collections as $collectionIndex => $collection) {
            $data = $this->objectManager->get($collection['model'])->getCollection();

            if (isset($collection['name'])) {
                $this->report['collections'][$collectionIndex]['name'] = $collection['name'];
            }

            if (isset($collection['attributes'])) {
                $data->addFieldToSelect($collection['attributes']);
            }

            // TODO: Test multiple filters and possibly build array in advance
            if (isset($collection['filters'])) {
                foreach ($collection['filters'] as $filter) {
                    $data->addFieldToFilter($filter['attribute'], [
                        $filter['condition'] => $filter['value']
                    ]);
                }
            }

            if (isset($collection['count'])) {
                $this->report['collections'][$collectionIndex]['data'] = $data->count();
            } else {
                $this->report['collections'][$collectionIndex]['data'] = $data->getData();
            }
        }
    }

    private function getHelpers()
    {
        if (!isset($this->report['helpers'])) {
            return;
        }

        $helpers = $this->report['helpers'];

        foreach ($helpers as $helperIndex => $helper) {
            $helperData = '';

            switch ($helper['path']) {
                case 'magento/edition':
                    $helperData = $this->productMetadata->getEdition();
                    break;
                case 'magento/version':
                    $helperData = $this->productMetadata->getVersion();
                    break;
                case 'magento/modules':
                    $helperData = array_keys((array) $this->moduleLoader->load());
                    break;
                case 'magento/module_version':
                    @$helperData = $this->moduleResource->getDbVersion($this->report['module']);  //TODO
                    break;
                case 'magento/locale':
                    $helperData = $this->resolver->getLocale();
                    break;
                case 'magento/applied_patches':
                    $helperData = $this->getPatches();
                    break;
            }

            $this->report['helpers'][$helperIndex]['value'] = $helperData;
        }
    }

    private function getPatches()
    {
        $io = new \Magento\Framework\Filesystem\Io\File();
        $patches = [];
        $patchFile = $this->directoryList->getPath('etc') . DIRECTORY_SEPARATOR . 'applied.patches.list';

        if (!$io->fileExists($patchFile)) {
            return [];
        }

        $io->open([
            'path' => $io->dirname($patchFile)
        ]);

        $io->streamOpen($patchFile, 'r');

        while ($buffer = $io->streamRead()) {
            if (stristr($buffer, '|')) {
                list($dateApplied, $patch, $magentoVersion, $patchVersion, $commitHash, $patchDate, $commitHead, $reverted) = array_map('trim', explode('|', $buffer));

                if (empty($reverted)) {
                    $patches[] = [
                        'date_applied' => $dateApplied,
                        'patch' => $patch,
                        'patch_version' => $patchVersion,
                        'patch_date' => $patchDate,
                        'magento_version' => $magentoVersion
                    ];
                }
            }
        }

        $io->streamClose();
        return $patches;
    }

    private function getServerInfo()
    {
        if (!isset($this->report['server'])) {
            return;
        }

        $serverFields = $this->report['server'];
        $serverInfo = $this->parseServerInfo();

        foreach ($serverFields as $fieldIndex => $field) {
            $fieldKeys = explode('/', $field['path']);
            $fieldValue = $serverInfo;

            foreach ($fieldKeys as $fieldKey) {
                if (isset($fieldValue[$fieldKey])) {
                    $fieldValue = $fieldValue[$fieldKey];
                }
            }

            $this->report['server'][$fieldIndex]['value'] = $fieldValue;
        }
    }

    private function getLogs()
    {
        if (!isset($this->report['logs'])) {
            return;
        }

        $logs = $this->report['logs'];

        foreach ($logs as $logIndex => $log) {
            if (isset($log['path'])) {
                $logResults = [];

                foreach (@glob($this->directoryList->getPath('log') . DIRECTORY_SEPARATOR . $log['path']) as $filename) {
                    $logResults[] = $filename;
                }

                if (isset($logResults[0])) {
                    $this->report['logs'][$logIndex]['value'] = $this->tailFile($logResults[0], $log['lines']);
                }
            }
        }
    }

    private function getExtra()
    {
        if (isset($this->report['integration_id'])) {
            try {
                $extraData = $this->dispatchEvent('plugindx_framework_report');
                $this->report['extra'] = $extraData;
            } catch (\Exception $e) {
                $this->report['extra'] = [
                    'error' => [
                        'message' => $e->getMessage()
                    ]
                ];
            }
        }
    }

    private function dispatchEvent($eventName)
    {
        $extraData = new \Magento\Framework\DataObject();
        $this->eventManager->dispatch($eventName . '_' . $this->report['integration_id'], ['extra_data', $extraData]);
        return $extraData;
    }

    private function parseServerInfo()
    {
        ob_start(); phpinfo(INFO_MODULES); $s = ob_get_contents(); ob_end_clean();

        $s = strip_tags($s, '<h2><th><td>');
        $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $s);
        $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $s);
        $t = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
        $r = []; $count = count($t);
        $p1 = '<info>([^<]+)<\/info>';
        $p2 = '/'.$p1.'\s*'.$p1.'\s*'.$p1.'/';
        $p3 = '/'.$p1.'\s*'.$p1.'/';

        for ($i = 1; $i < $count; $i++) {
            if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $t[$i], $matchs)) {
                $name = trim($matchs[1]);
                $vals = explode("\n", $t[$i + 1]);
                foreach ($vals AS $val) {
                    if (preg_match($p2, $val, $matchs)) {
                        $r[$name][trim($matchs[1])] = [trim($matchs[2]), trim($matchs[3])];
                    } elseif (preg_match($p3, $val, $matchs)) {
                        $r[$name][trim($matchs[1])] = trim($matchs[2]);
                    }
                }
            }
        }

        return $r;
    }

    /**
     * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
     * @author Torleif Berger, Lorenzo Stanco
     * @link http://stackoverflow.com/a/15025877/995958
     * @license http://creativecommons.org/licenses/by/3.0/
     */
    private function tailFile($filepath, $lines = 100, $adaptive = true) {
        $f = @fopen($filepath, "rb");
        if (false === $f) {
            return false;
        }
        if (!$adaptive) {
            $buffer = 4096;
        } else {
            $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
        }
        fseek($f, -1, SEEK_END);
        if ("\n" != fread($f, 1)) {
            $lines -= 1;
        }
        $output = '';
        $chunk = '';
        while (ftell($f) > 0 && $lines >= 0) {
            $seek = min(ftell($f), $buffer);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)) . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");
        }
        while ($lines++ < 0) {
            $output = substr($output, strpos($output, "\n") + 1);
        }
        fclose($f);
        return trim($output);
    }
}
