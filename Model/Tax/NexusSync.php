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
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Model\Tax;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Tax\NexusFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class NexusSync extends \Taxjar\SalesTax\Model\Tax\Nexus
{
    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $_clientFactory;
    
    /**
     * @var \Taxjar\SalesTax\Model\Tax\NexusFactory
     */
    protected $_nexusFactory;
    
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;
    
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param ClientFactory $clientFactory
     * @param NexusFactory $nexusFactory
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        ClientFactory $clientFactory,
        NexusFactory $nexusFactory,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_clientFactory = $clientFactory;
        $this->_nexusFactory = $nexusFactory;
        $this->_regionFactory = $regionFactory;
        $this->_countryFactory = $countryFactory;
        $this->_scopeConfig = $scopeConfig;
    }
    
    /**
     * Create or update nexus address in TaxJar
     *
     * @return void
     */
    public function sync()
    {
        $client = $this->_clientFactory->create();
        $apiKey = trim($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY));

        $data = [
            'street' => $this->getStreet(),
            'city' => $this->getCity(),
            'state' => $this->getRegionCode(),
            'zip' => $this->getPostcode(),
            'country' => $this->getCountryId()
        ];
        
        $responseErrors = [
            '400' => __('Your nexus address contains invalid data. Please verify the address in order to sync with TaxJar.'),
            '409' => __('A nexus address already exists for this state/region. TaxJar currently supports one address per region.'),
            '422' => __('Your nexus address is missing one or more required fields. Please verify the address in order to sync with TaxJar.'),
            '500' => __('Something went wrong while syncing your address with TaxJar. Please verify the address and contact support@taxjar.com if the problem persists.')
        ];
        
        if ($this->getId()) {
            $client->putResource($apiKey, 'nexus', $this->getApiId(), $data, $responseErrors);
        } else {
            $savedAddress = $client->postResource($apiKey, 'nexus', $data, $responseErrors);
            $this->setApiId($savedAddress['id']);
        }
    }
    
    /**
     * Delete nexus address in TaxJar
     *
     * @return void
     */
    public function syncDelete()
    {
        $client = $this->_clientFactory->create();
        $apiKey = trim($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY));
        
        $responseErrors = [
            '409' => __('A nexus address with this ID could not be found in TaxJar.'),
            '500' => __('Something went wrong while deleting your address in TaxJar. Please contact support@taxjar.com if the problem persists.')
        ];

        if ($this->getId()) {
            $client->deleteResource($apiKey, 'nexus', $this->getApiId(), $responseErrors);
        }
    }
    
    /**
     * Sync nexus addresses from TaxJar -> Magento
     *
     * @return void
     */
    public function syncCollection()
    {
        $client = $this->_clientFactory->create();
        $apiKey = trim($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY));
        $nexusJson = $client->getResource($apiKey, 'nexus');

        if ($nexusJson['addresses']) {
            $addresses = $nexusJson['addresses'];

            foreach ($addresses as $address) {
                $addressRegion = $this->_regionFactory->create()->loadByCode($address['state'], $address['country']);
                $addressCountry = $this->_countryFactory->create()->loadByCode($address['country']);
                $addressCollection = $this->_nexusFactory->create()->getCollection();
                
                // Find existing address by region if US, otherwise country
                if ($address['country'] == 'US') {
                    $existingAddress = $addressCollection->addRegionFilter($addressRegion->getId())->getFirstItem();
                } else {
                    $existingAddress = $addressCollection->addCountryFilter($addressCountry->getId())->getFirstItem();
                }
                
                if ($existingAddress->getId()) {
                    $existingAddress->addData([
                        'api_id'     => $address['id'],
                        'street'     => $address['street'],
                        'city'       => $address['city'],
                        'postcode'   => $address['zip']
                    ]);
                    $existingAddress->save();
                } else {
                    $newAddress = $this->_nexusFactory->create();
                    $newAddress->setData([
                        'api_id'      => $address['id'],
                        'street'      => $address['street'],
                        'city'        => $address['city'],
                        'country_id'  => $addressCountry->getId(),
                        'region'      => $addressRegion->getName(),
                        'region_id'   => $addressRegion->getId(),
                        'region_code' => $addressRegion->getCode(),
                        'postcode'    => $address['zip']
                    ]);
                    $newAddress->save();
                }
            }
        }
    }
}