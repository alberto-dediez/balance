<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Property;

use App\Property\Domain\Model\Brand\BrandRepository;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandMother;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final readonly class PropertyContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
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

    private function getCompanyRepository(): CompanyRepository
    {
        return $this->client->getContainer()->get(CompanyRepository::class);
    }

    #[Given('property :name with id :id exists')]
    public function propertyWithIdExists(string $name, string $id): void
    {
        $property = PropertyMother::fromNameAndId($name, $id);

        $this->getPropertyRepository()->add($property);
    }

    #[Given('property :name with id :id of account :account exists')]
    public function propertyWithIdOfAccountExists(string $name, string $id, string $account): void
    {
        $property = PropertyMother::fromNameAndAccountAndId($name, $account, $id);

        $this->getPropertyRepository()->add($property);
    }

    #[Given('country :name with id :id exists')]
    public function countryWithIdExists(string $name, string $id): void
    {
        $country = CountryMother::fromNameAndId($name, $id);

        $this->getCountryRepository()->add($country);
    }

    #[Given('country :name with iso :iso and id :id exists')]
    public function countryWithIsoAndIdExists(string $name, string $iso, string $id): void
    {
        $country = CountryMother::fromNameAndIsoAndId($name, $iso, $id);

        $this->getCountryRepository()->add($country);
    }

    #[Given('destination :name with id :id exists')]
    public function destinationWithIdExists(string $name, string $id): void
    {
        $destination = DestinationMother::fromNameAndId($name, $id);

        $this->getDestinationRepository()->add($destination);
    }

    #[Given('destination :name of country :country with id :id exists')]
    public function destinationOfCountryWithIdExists(string $name, string $country, string $id): void
    {
        $destination = DestinationMother::fromNameAndCountryAndId($name, $country, $id);

        $this->getDestinationRepository()->add($destination);
    }

    #[Given('category :name with id :id exists')]
    public function categoryWithIdExists(string $name, string $id): void
    {
        $category = CategoryMother::fromNameAndId($name, $id);

        $this->getCategoryRepository()->add($category);
    }

    #[Given('brand :name with id :id exists')]
    public function brandWithIdExists(string $name, string $id): void
    {
        $brand = BrandMother::fromNameAndId($name, $id);

        $this->getBrandRepository()->add($brand);
    }

    #[Given('company :name with id :id exists')]
    public function companyWithIdExists(string $name, string $id): void
    {
        $company = CompanyMother::fromNameAndId($name, $id);

        $this->getCompanyRepository()->add($company);
    }

    #[Given('company :name with id :id of account :account exists')]
    public function companyWithIdOfAccountExists(string $name, string $id, string $account): void
    {
        $company = CompanyMother::fromNameAndAccountAndId($name, $account, $id);

        $this->getCompanyRepository()->add($company);
    }

    #[Given('property :property has company :company assigned')]
    public function propertyHasCompanyAssigned(string $property, string $company): void
    {
        $property = $this->getPropertyRepository()->find(new PropertyId($property));
        $company = $this->getCompanyRepository()->find(new CompanyId($company));

        assert(null !== $property && null !== $company);

        $property->update(
            name: $property->name(),
            accountId: $property->accountId(),
            info: $property->info(),
            externalId: $property->externalId(),
            companyId: $company->id(),
        );
    }

    #[Then('property :id has place location :label at :latitude, :longitude')]
    public function propertyHasPlaceLocation(string $id, string $label, string $latitude, string $longitude): void
    {
        $property = $this->getPropertyRepository()->find(new PropertyId($id));

        Assert::assertNotNull($property);
        $this->assertPlaceLocation($property, $label, $latitude, $longitude);
    }

    #[Then('property named :name has place location :label at :latitude, :longitude')]
    public function propertyNamedHasPlaceLocation(string $name, string $label, string $latitude, string $longitude): void
    {
        $properties = $this->getPropertyRepository()->findByFilter(new PropertyFilter(name: $name));

        Assert::assertCount(1, $properties);

        $property = reset($properties);

        Assert::assertInstanceOf(Property::class, $property);
        $this->assertPlaceLocation($property, $label, $latitude, $longitude);
    }

    private function assertPlaceLocation(Property $property, string $label, string $latitude, string $longitude): void
    {
        Assert::assertSame($label, $property->info()->placeLabel());
        Assert::assertSame((float) $latitude, (float) $property->info()->latitude());
        Assert::assertSame((float) $longitude, (float) $property->info()->longitude());
    }
}
