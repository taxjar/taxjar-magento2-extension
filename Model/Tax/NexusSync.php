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

namespace Taxjar\SalesTax\Model\Tax;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Tax\NexusFactory;

class NexusSync extends \Taxjar\SalesTax\Model\Tax\Nexus
{
    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Tax\NexusFactory
     */
    protected $nexusFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

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
        $this->clientFactory = $clientFactory;
        $this->nexusFactory = $nexusFactory;
        $this->regionFactory = $regionFactory;
        $this->countryFactory = $countryFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Create or update nexus address in TaxJar
     *
     * @return void
     */
    public function sync()
    {
        $client = $this->clientFactory->create();

        $data = [
            'street' => $this->getStreet(),
            'city' => $this->getCity(),
            'state' => $this->getRegionCode(),
            'zip' => $this->getPostcode(),
            'country' => $this->getCountryId()
        ];

        // @codingStandardsIgnoreStart
        $responseErrors = [
            '400' => __('Your nexus address contains invalid data. Please verify the address in order to sync with TaxJar.'),
            '409' => __('A nexus address already exists for this state/region. TaxJar currently supports one address per region.'),
            '422' => __('Your nexus address is missing one or more required fields. Please verify the address in order to sync with TaxJar.'),
            '500' => __('Something went wrong while syncing your address with TaxJar. Please verify the address and contact support@taxjar.com if the problem persists.')
        ];
        // @codingStandardsIgnoreEnd

        if ($this->getId()) {
            $client->putResource('nexus', $this->getApiId(), $data, $responseErrors);
        } else {
            $savedAddress = $client->postResource('nexus', $data, $responseErrors);
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
        $client = $this->clientFactory->create();

        // @codingStandardsIgnoreStart
        $responseErrors = [
            '409' => __('A nexus address with this ID could not be found in TaxJar.'),
            '500' => __('Something went wrong while deleting your address in TaxJar. Please contact support@taxjar.com if the problem persists.')
        ];
        // @codingStandardsIgnoreEnd

        if ($this->getId()) {
            $client->deleteResource('nexus', $this->getApiId(), $responseErrors);
        }
    }

    /**
     * Sync nexus addresses from TaxJar -> Magento
     *
     * @return void
     */
    public function syncCollection()
    {
        $client = $this->clientFactory->create();
        $nexusJson = $client->getResource('nexus');

        if ($nexusJson['addresses']) {
            $addresses = $nexusJson['addresses'];

            foreach ($addresses as $address) {
                if (!isset($address['country']) || empty($address['country'])) {
                    continue;
                }

                if (($address['country'] == 'US' || $address['country'] == 'CA') && (!isset($address['state']) || empty($address['state']))) {
                    continue;
                }

                $addressRegion = $this->regionFactory->create()->loadByCode($address['state'], $address['country']);
                $addressCountry = $this->countryFactory->create()->loadByCode($address['country']);
                $addressCollection = $this->nexusFactory->create()->getCollection();

                // Find existing address by region if US, otherwise country
                // @codingStandardsIgnoreStart
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
                    $newAddress = $this->nexusFactory->create();
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
                // @codingStandardsIgnoreEnd
            }
        }
    }
}
