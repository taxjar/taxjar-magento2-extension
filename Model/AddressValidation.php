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

use Taxjar\SalesTax\Api\AddressValidationInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class AddressValidation implements AddressValidationInterface
{
    /**
     * @var \Taxjar\SalesTax\Model\Client $client
     */
    protected $client;

    /**
     * @var \Taxjar\SalesTax\Model\Logger $logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @param ClientFactory $clientFactory
     * @param Logger $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     */
    public function __construct(
        \Taxjar\SalesTax\Model\ClientFactory $clientFactory,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory
    ) {
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_ADDRVALIDATION_LOG);
        $this->client = $clientFactory->create();
        $this->client->showResponseErrors(true);
        $this->scopeConfig = $scopeConfig;
        $this->regionFactory = $regionFactory;
        $this->countryFactory = $countryFactory;
    }

    /**
     * Return if address validation is currently enabled
     *
     * @return bool|mixed
     */
    public function canValidateAddress()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $validateAddress = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_ADDRESS_VALIDATION, $storeScope);

        return (bool)$validateAddress;
    }

    /**
     * Endpoint that accepts an address and returns suggestions to improve it's accuracy
     *
     * @param null $street0
     * @param null $street1
     * @param null $city
     * @param null $region
     * @param null $country
     * @param null $postcode
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateAddress(
        $street0 = null,
        $street1 = null,
        $city = null,
        $region = null,
        $country = null,
        $postcode = null
    ) {
        $errorResponse = ['error' => true, 'error_msg' => 'Unable to validate your address.'];

        // Ensure address validation is enabled
        if (!$this->canValidateAddress()) {
            return $errorResponse;
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
            return $errorResponse;
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
                }
            }
        }

        return $results;
    }

    /**
     * Format the input to match the API's expectations
     *
     * @param $addr
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
            if (!is_null($addr['street0'])) {
                $street[] = $addr['street0'];
            }
            unset($addr['street0']);
        }
        if (isset($addr['street1']) || array_key_exists('street1', $addr)) {
            if (!is_null($addr['street1'])) {
                $street[] = $addr['street1'];
            }
            unset($addr['street1']);
        }
        if (isset($addr['street2']) || array_key_exists('street1', $addr)) {
            if (!is_null($addr['street2'])) {
                $street[] = $addr['street2'];
            }
            unset($addr['street2']);
        }
        $addr['street'] = implode(", ", $street);

        if (isset($addr['postcode']) || array_key_exists('postcode', $addr)) {
            if (!is_null($addr['postcode'])) {
                $addr['zip'] = $addr['postcode'];
            }
            unset($addr['postcode']);
        }

        return $addr;
    }

    /**
     * Ensure the address is eligible for validation
     *
     * @param $addr
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

        return $addr;
    }

    /**
     * Post the request to the TaxJar API and handle any responses
     *
     * @param $data
     * @return array|bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateWithTaxjar($data)
    {
        try {
            $response = $this->client->postResource('addressValidation', $data);
            $response = $this->formatResponse($response);
        } catch (\Exception $e) {
            $msg = json_decode($e->getMessage());

            switch ($msg->status) {
                case 404:  // no suggested addresses found
                    $response = false;
                    break;
                default:
                    $this->logger->log($e->getMessage());
                    $response = false;
            }
        }
        return $response;
    }

    /**
     * Format the response to match Magento's expectations
     *
     * @param $response
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

                ];
            }
        }

        return $response;
    }

    /**
     * Calculate the difference between two addresses and wrap the changes in HTML for highlighting
     *
     * @param $orig
     * @param $address
     * @param $id
     * @return array
     */
    protected function highlightChanges($orig, $address, $id)
    {
        $changes = $address;
        $changesMade = false;

        //TODO: a better diff implementation
        // a number of options:  https://stackoverflow.com/questions/321294/highlight-the-difference-between-two-strings-in-php

        foreach ($orig as $k => $v) {
            if (isset($orig[$k]) && isset($address[$k]) && $orig[$k] !== $address[$k]) {
                $changesMade = true;
                if (is_array($orig[$k])) {
                    $changes[$k][0] = '<span class="suggested-address-diff">' . $address[$k][0] . '</span>';
                } else {
                    $changes[$k] = '<span class="suggested-address-diff">' . $address[$k] . '</span>';
                }
            }
        }

        if ($changesMade) {
            return ['id' => $id, 'address' => $address, 'changes' => $changes];
        }

        return [];
    }

    /**
     * @param $regionId
     * @return \Magento\Directory\Model\Region
     */
    protected function getRegionById($regionId)
    {
        /** @var \Magento\Directory\Model\Region $region */
        $region = $this->regionFactory->create();
        $region->load($regionId);
        return $region;
    }

    /**
     * @param $regionCode
     * @param $countryId
     * @return \Magento\Directory\Model\Region
     */
    protected function getRegionByCode($regionCode, $countryId)
    {
        /** @var \Magento\Directory\Model\Region $region */
        $region = $this->regionFactory->create();
        $region->loadByCode($regionCode, $countryId);
        return $region;
    }

    /**
     * @param $countryId
     * @return \Magento\Directory\Model\Country
     */
    protected function getCountryById($countryId)
    {
        $country = $this->countryFactory->create();
        $country->load($countryId);
        return $country;
    }
}
