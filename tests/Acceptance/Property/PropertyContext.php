<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Property;

use Behat\Step\Given;
use Behat\Behat\Context\Context;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;

final class PropertyContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    private function getPropertyRepository(): PropertyRepository
    {
        return $this->client->getContainer()->get(PropertyRepository::class);
    }

    private function getDestinationRepository(): DestinationRepository
    {
        return $this->client->getContainer()->get(DestinationRepository::class);
    }

    private function getCountryRepository(): CountryRepository
    {
        return $this->client->getContainer()->get(CountryRepository::class);
    }

    private function getCategoryRepository(): CategoryRepository
    {
        return $this->client->getContainer()->get(CategoryRepository::class);
    }

    private function getBrandRepository(): BrandRepository
    {
        return $this->client->getContainer()->get(BrandRepository::class);
    }

    #[Given('property :name with id :id exists')]
    public function propertyWithIdExists($name, $id): void
    {
        $property = PropertyMother::fromNameAndId($name, $id);
        
        $this->getPropertyRepository()->add($property);      
    }    

    #[Given('property :name with id :id of account :account exists')]
    public function propertyWithIdOfAccountExists($name, $id, $account): void
    {
        $property = PropertyMother::fromNameAndAccountAndId($name, $account, $id);
        
        $this->getPropertyRepository()->add($property);      
    }

    #[Given('country :name with id :id exists')]
    public function countryWithIdExists($name, $id): void
    {
        $country = CountryMother::fromNameAndId($name, $id);
        
        $this->getCountryRepository()->add($country);      
    } 

    #[Given('destination :name with id :id exists')]
    public function destinationWithIdExists($name, $id): void
    {
        $destination = DestinationMother::fromNameAndId($name, $id);
        
        $this->getDestinationRepository()->add($destination);      
    } 

    #[Given('destination :name of country :country with id :id exists')]
    public function destinationOfCountryWithIdExists($name, $country, $id): void
    {
        $destination = DestinationMother::fromNameAndCountryAndId($name, $country, $id);
        
        $this->getDestinationRepository()->add($destination);      
    } 

    #[Given('category :name with id :id exists')]
    public function categoryWithIdExists($name, $id): void
    {
        $category = CategoryMother::fromNameAndId($name, $id);
        
        $this->getCategoryRepository()->add($category);      
    } 

    #[Given('brand :name with id :id exists')]
    public function brandWithIdExists($name, $id): void
    {
        $brand = BrandMother::fromNameAndId($name, $id);
        
        $this->getBrandRepository()->add($brand);
    }
}
