<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Fixture\Customer;

use Exception;
use InvalidArgumentException;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Directory\Model\Region;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use stdClass;

/**
 * Builder to be used by fixtures
 */
class AddressBuilder
{
    private const ADDRESS_MAP = [
        'en_US' => [
            'postcode' => '01888',
            'province' => null,
            'state' => 'Massachusetts',
            'city' => 'Woburn',
            'streetAddress' => '462 Washington St',
            'company' => 'TPS Unlimited, Inc.',
            'phoneNumber' => '999-999-9999',
            'lastName' => 'User',
            'firstName' => 'Test',
        ],
        'en_CA' => [
            'postcode' => 'T2V 2W2',
            'province' => 'Alberta',
            'state' => null,
            'city' => 'Calgary',
            'streetAddress' => '3452 Heritage Dr',
            'company' => 'Ehh?',
            'phoneNumber' => '403-554-9551',
            'lastName' => 'User',
            'firstName' => 'Test',
        ],
        'en_AU' => [
            'postcode' => '4684',
            'province' => null,
            'state' => 'New South Wales',
            'city' => 'West Sandra',
            'streetAddress' => '947 Christopher Villas',
            'company' => 'Gday',
            'phoneNumber' => '403-554-9551',
            'lastName' => 'User',
            'firstName' => 'Test',
        ],
    ];

    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    public function __construct(AddressRepositoryInterface $addressRepository, AddressInterface $address)
    {
        $this->address = $address;
        $this->addressRepository = $addressRepository;
    }

    public function __clone()
    {
        $this->address = clone $this->address;
    }

    public static function anAddress(
        string $locale = 'en_US'
    ): AddressBuilder {
        $objectManager = Bootstrap::getObjectManager();

        $address = self::prepareFakeAddress($objectManager, $locale);
        return new self($objectManager->create(AddressRepositoryInterface::class), $address);
    }

    public static function aCompanyAddress(
        string $locale = 'en_US',
        string $vatId = '1234567890'
    ): AddressBuilder {
        $objectManager = Bootstrap::getObjectManager();

        $address = self::prepareFakeAddress($objectManager, $locale);
        $address->setVatId($vatId);
        return new self($objectManager->create(AddressRepositoryInterface::class), $address);
    }

    public function asDefaultShipping(): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setIsDefaultShipping(true);
        return $builder;
    }

    public function asDefaultBilling(): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setIsDefaultBilling(true);
        return $builder;
    }

    public function withPrefix(string $prefix): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setPrefix($prefix);
        return $builder;
    }

    public function withFirstname(string $firstname): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setFirstname($firstname);
        return $builder;
    }

    public function withMiddlename(string $middlename): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setMiddlename($middlename);
        return $builder;
    }

    public function withLastname(string $lastname): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setLastname($lastname);
        return $builder;
    }

    public function withSuffix(string $suffix): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setSuffix($suffix);
        return $builder;
    }

    public function withStreet(string $street): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setStreet((array)$street);
        return $builder;
    }

    public function withCompany(string $company): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setCompany($company);
        return $builder;
    }

    public function withTelephone(string $telephone): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setTelephone($telephone);
        return $builder;
    }

    public function withPostcode(string $postcode): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setPostcode($postcode);
        return $builder;
    }

    public function withCity(string $city): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setCity($city);
        return $builder;
    }

    public function withCountryId(string $countryId): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setCountryId($countryId);
        return $builder;
    }

    public function withRegionId(int $regionId): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setRegionId($regionId);
        return $builder;
    }

    /**
     * @param mixed[] $values
     * @return AddressBuilder
     */
    public function withCustomAttributes(array $values): AddressBuilder
    {
        $builder = clone $this;
        foreach ($values as $code => $value) {
            $builder->address->setCustomAttribute($code, $value);
        }
        return $builder;
    }

    /**
     * @return AddressInterface
     * @throws LocalizedException
     */
    public function build(): AddressInterface
    {
        return $this->addressRepository->save($this->address);
    }

    public function buildWithoutSave(): AddressInterface
    {
        return clone $this->address;
    }

    private static function prepareFakeAddress(
        ObjectManagerInterface $objectManager,
        string $locale = 'en_US'
    ): AddressInterface {
        $fakeAddress = self::getAddressObject($locale);
        $countryCode = substr($locale, -2);

        $region = $fakeAddress->province ?? $fakeAddress->state;

        $regionId = $objectManager->create(Region::class)->loadByName($region, $countryCode)->getId();

        /** @var AddressInterface $address */
        $address = $objectManager->create(AddressInterface::class);
        $address
            ->setTelephone($fakeAddress->phoneNumber)
            ->setPostcode($fakeAddress->postcode)
            ->setCountryId($countryCode)
            ->setCity($fakeAddress->city)
            ->setCompany($fakeAddress->company)
            ->setStreet([$fakeAddress->streetAddress])
            ->setLastname($fakeAddress->lastName)
            ->setFirstname($fakeAddress->firstName)
            ->setRegionId($regionId);

        return $address;
    }

    private static function getAddressObject(string $locale): stdClass
    {
        try {
            return (object) self::ADDRESS_MAP[$locale];
        } catch (Exception $e) {
            throw new Exception('Could not create fake address with unsupported locale.');
        }
    }
}
