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

use Exception;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Taxjar\SalesTax\Api\AddressValidationInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class AddressValidation implements AddressValidationInterface
{
    /**
     * @var Client $client
     */
    protected $client;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @param ClientFactory $clientFactory
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param CacheInterface $cache
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ClientFactory $clientFactory,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        CacheInterface $cache,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_ADDRVALIDATION_LOG);
        $this->client = $clientFactory->create();
        $this->client->showResponseErrors(true);
        $this->scopeConfig = $scopeConfig;
        $this->regionFactory = $regionFactory;
        $this->countryFactory = $countryFactory;
        $this->cache = $cache;
        $this->_storeManager = $storeManager;
    }

    /**
     * Parse an address and return suggestions to improve accuracy
     *
     * @param string $street0
     * @param string $street1
     * @param string $city
     * @param string $region
     * @param string $country
     * @param string $postcode
     * @param string $storeCode
     * @return array|mixed
     * @throws LocalizedException
     */
    public function validateAddress(
        $street0 = null,
        $street1 = null,
        $city = null,
        $region = null,
        $country = null,
        $postcode = null,
        $storeCode = null
    ) {
        $storeId = $this->getStoreIdByCode($storeCode);

        // Ensure address validation is enabled
        if (!$this->canValidateAddress($storeId)) {
            return [];
        }

        // Format the address to match the endpoint's naming convention
        $addr = $this->formatInput([
            'street0' => $street0,
            'street1' => $street1,
            'city' => $city,
            'region' => $region,
            'country' => $country,
            'postcode' => $postcode
        ]);

        // Validate address data locally
        if ($this->validateInput($addr) === false) {
            return [];
        }

        $results = [];
        $original = [
            'street' => [$street0],
            'city' => $city,
            'regionCode' => $this->getRegionById($region)->getCode(),
            'regionId' => $region,
            'countryId' => $country,
            'postcode' => $postcode
        ];

        $results[] = ['id' => 0, 'address' => $original, 'changes' => $original];

        // Send address to Taxjar for validation
        $response = $this->validateWithTaxjar($addr);

        // Respond with address suggestions (if any)
        if ($response !== false && isset($response['addresses']) && is_array($response['addresses'])) {
            foreach ($response['addresses'] as $id => $address) {
                $result = $this->highlightChanges($original, $address, ++$id);
                if (!empty($result)) {
                    $results[] = $result;
                } else {
                    $results[0]['address']['custom_attributes']['suggestedAddress'] = true;
                }
            }
        }

        return $results;
    }

    /**
     * Return if address validation is currently enabled
     *
     * @return bool
     */
    protected function canValidateAddress($scopeId = 0)
    {
        return (bool) $this->scopeConfig->getValue(
            TaxjarConfig::TAXJAR_ADDRESS_VALIDATION,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Returns current store id or default.
     *
     * @param string $storeCode
     * @return int
     */
    protected function getStoreIdByCode($storeCode = null)
    {
        if ($storeCode) {
            $storesByCode = $this->_storeManager->getStores(false, true);
            if (isset($storesByCode[$storeCode])) {
                /** @var Store $store */
                $store = $storesByCode[$storeCode];
                return $store->getId();
            }
        }

        return 0;
    }

    /**
     * Format the input to match the API's expectations
     *
     * @param array $addr
     * @return mixed
     */
    protected function formatInput($addr)
    {
        if (isset($addr['region']) && is_numeric($addr['region'])) {
            $region = $this->getRegionById($addr['region']);
            $addr['state'] = $region->getCode();
            unset($addr['region']);
        }

        $street = [];

        if (isset($addr['street0']) || array_key_exists('street0', $addr)) {
            if ($addr['street0'] !== null) {
                $street[] = $addr['street0'];
            }
            unset($addr['street0']);
        }

        if (isset($addr['street1']) || array_key_exists('street1', $addr)) {
            if ($addr['street1'] !== null) {
                $street[] = $addr['street1'];
            }
            unset($addr['street1']);
        }

        if (isset($addr['street2']) || array_key_exists('street1', $addr)) {
            if ($addr['street2'] !== null) {
                $street[] = $addr['street2'];
            }
            unset($addr['street2']);
        }

        $addr['street'] = implode(", ", $street);

        if (isset($addr['postcode']) || array_key_exists('postcode', $addr)) {
            if ($addr['postcode'] !== null) {
                $addr['zip'] = $addr['postcode'];
            }
            unset($addr['postcode']);
        }

        return $addr;
    }

    /**
     * Ensure the address is eligible for validation
     *
     * @param array $addr
     * @return bool
     */
    protected function validateInput($addr)
    {
        if (empty($addr) || !is_array($addr)) {
            return false;
        }

        // Only US addresses can be validated
        if (empty($addr['country']) || $addr['country'] !== 'US') {
            return false;
        }

        // Minimum of street and city/state or zip must be provided
        if (empty($addr['street']) && ((empty($addr['city']) && empty($addr['state'])) || empty($addr['postcode']))) {
            return false;
        }

        return true;
    }

    /**
     * Post the request to the TaxJar API and handle any responses
     *
     * @param mixed $data
     * @return array|bool|mixed
     * @throws LocalizedException
     */
    protected function validateWithTaxjar($data)
    {
        try {
            $cacheKey = implode(";", $data);
            $response = $this->cache->load($cacheKey);

            if (empty($response)) {
                $this->logger->log('Validating address: ' . json_encode($data), 'post');
                $response = $this->client->postResource('addressValidation', $data);
                $this->logger->log('Successful API response: ' . json_encode($response), 'success');
                $response = $this->formatResponse($response);
                $this->cache->save(json_encode($response), $cacheKey, [], 3600);
            } else {
                $response = json_decode($response, true);
            }
        } catch (Exception $e) {
            $errorMessage = json_decode($e->getMessage());
            $response = false;

            $this->logger->log(
                $errorMessage->status . ' ' . $errorMessage->error . ' - ' . $errorMessage->detail,
                'error'
            );
        }

        return $response;
    }

    /**
     * Format the response to match Magento's expectations
     *
     * @param array $response
     * @return mixed
     */
    protected function formatResponse($response)
    {
        if (isset($response['addresses']) && is_array($response['addresses'])) {
            foreach ($response['addresses'] as $k => $address) {
                $region = $this->getRegionByCode($address['state'], $address['country']);
                $response['addresses'][$k] = [
                    'street' => [$address['street']],
                    'city' => $address['city'],
                    'regionCode' => $address['state'],
                    'regionId' => $region->getId(),
                    'countryId' => $address['country'],
                    'postcode' => $address['zip'],
                    'custom_attributes' => [
                        'suggestedAddress' => true
                    ]
                ];
            }
        }

        return $response;
    }

    /**
     * Calculate the difference between two addresses and wrap the changes in HTML for highlighting
     *
     * @param array $original
     * @param array $new
     * @param int $id
     * @return array
     */
    protected function highlightChanges($original, $new, $id)
    {
        $changes = $new;
        $changesMade = false;

        foreach ($original as $k => $v) {
            if (isset($new[$k]) && $original[$k] !== $new[$k]) {
                $changesMade = true;
                if (is_array($original[$k])) {
                    $changes[$k][0] = $this->htmlDiff($original[$k][0], $new[$k][0]);
                } else {
                    $changes[$k] = $this->htmlDiff($original[$k], $new[$k]);
                }
            }
        }

        if ($changesMade) {
            return ['id' => $id, 'address' => $new, 'changes' => $changes];
        }

        return [];
    }

    /**
     * Paul's Simple Diff Algorithm v 0.1
     * https://github.com/paulgb/simplediff
     *
     * @param array $old
     * @param array $new
     * @return array
     */
    public function simplediff($old, $new)
    {
        $matrix = [];
        $maxlen = 0;
        $oMax = 0;
        $nMax = 0;

        foreach ($old as $oIndex => $oValue) {
            foreach (array_keys($new, $oValue) as $nIndex) {
                $matrix[$oIndex][$nIndex] = isset($matrix[$oIndex - 1][$nIndex - 1]) ?
                    $matrix[$oIndex - 1][$nIndex - 1] + 1 : 1;
                if ($matrix[$oIndex][$nIndex] > $maxlen) {
                    $maxlen = $matrix[$oIndex][$nIndex];
                    $oMax = $oIndex + 1 - $maxlen;
                    $nMax = $nIndex + 1 - $maxlen;
                }
            }
        }

        if ($maxlen == 0) {
            return [['d' => $old, 'i' => $new]];
        }

        return array_merge(
            $this->simplediff(array_slice($old, 0, $oMax), array_slice($new, 0, $nMax)),
            array_slice($new, $nMax, $maxlen),
            $this->simplediff(array_slice($old, $oMax + $maxlen), array_slice($new, $nMax + $maxlen))
        );
    }

    /**
     * Wrap differences between two strings in html
     * https://github.com/paulgb/simplediff
     *
     * @param string $old
     * @param string $new
     * @return string
     */
    public function htmlDiff($old, $new)
    {
        $ret = '';
        $pattern = "/[\s]+/";

        // Explode $old and $new into arrays based on whitespace
        $simplediff = $this->simplediff(preg_split($pattern, (string) $old), preg_split($pattern, (string) $new));

        // Wrap each difference in a span for highlighting
        foreach ($simplediff as $diff) {
            if (is_array($diff)) {
                $ret .= (!empty($diff['i']) ? '<span class="suggested-address-diff">' .
                    implode(' ', $diff['i']) . '</span> ' : '');
            } else {
                $ret .= $diff . ' ';
            }
        }

        return $ret;
    }

    /**
     * @param int $regionId
     * @return Region
     */
    protected function getRegionById($regionId)
    {
        /** @var Region $region */
        $region = $this->regionFactory->create();
        $region->load($regionId);
        return $region;
    }

    /**
     * @param int $regionCode
     * @param int $countryId
     * @return Region
     */
    protected function getRegionByCode($regionCode, $countryId)
    {
        /** @var Region $region */
        $region = $this->regionFactory->create();
        $region->loadByCode($regionCode, $countryId);
        return $region;
    }

    /**
     * @param int $countryId
     * @return Country
     */
    protected function getCountryById($countryId)
    {
        $country = $this->countryFactory->create();
        $country->load($countryId);
        return $country;
    }
}
