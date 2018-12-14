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

    const ADDRESS_VALIDATION_SCOPE_PATH = 'tax/taxjar/address_validation';

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

    public function __construct(
        \Taxjar\SalesTax\Model\ClientFactory $clientFactory,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory
    )
    {
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
        $validateAddress = $this->scopeConfig->getValue(self::ADDRESS_VALIDATION_SCOPE_PATH, $storeScope);

        return (bool)$validateAddress;
    }

    public function validateAddress($street0 = null, $street1 = null, $city = null, $region = null, $country = null, $postcode = null)
    {
        $errorResponse = ['error' => true, 'error_msg' => 'Unable to validate your address.'];

        if (!$this->canValidateAddress()) {
            return json_encode($errorResponse);
        }

        $addr = ['street0' => $street0, 'street1' => $street1, 'city' => $city, 'region' => $region, 'country' => $country, 'postcode' => $postcode];

        // Validate address data locally
        $addr = $this->validateInput($addr);

        if ($addr === false) {
            return json_encode($errorResponse);
        }

        // Send address to Taxjar for validation
        $response = $this->validateWithTaxjar($addr);

        // Respond with address suggestions (if any)
        if ($response !== false && isset($response['addresses']) && is_array($response['addresses'])) {

            $results = ['original' => $addr, 'suggestions' => []];
            foreach ($response['addresses'] as $address) {
                $results['suggestions'][] = $this->highlightChanges($addr, $address);
            }
            return json_encode($results);
        }

        return json_encode($errorResponse);
    }

    protected function highlightChanges($orig, $address)
    {
        $changes = [];

        //TODO: a better diff implementation
        // a number of options:  https://stackoverflow.com/questions/321294/highlight-the-difference-between-two-strings-in-php

        foreach ($orig as $k => $v) {
            if ($orig[$k] !== $address[$k]) {
                $changes[$k] = $orig[$k] . ' <span class="diff">' . str_replace($orig[$k], '', $address[$k]) . '</span>';
            }
        }

        return ['address' => $address, 'changes' => $changes];
    }


    //
    protected function validateInput($addr)
    {
        if (!is_array($addr)) {
            return false;
        }

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

        //TODO: confirm which values are necessary for address validation
        if (empty($addr['street']) && (empty($addr['city']) || empty($addr['postcode']))) {
//            return false;
        }

        return $addr;
    }

    protected function validateWithTaxjar($data)
    {
        try {
            $response = $this->client->postResource('addressValidation', $data);
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage());
            //TODO: add error message to response if it's relevant to user
            $response = false;
        }
        return $response;
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

