<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompletedPropertyFootprintFinder;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use PHPUnit\Framework\TestCase;

class CompletedPropertyFootprintFinderTest extends TestCase
{
    private const ACCOUNT_ID = 'account-1';
    private const COMPANY_ID = 'company-1';
    private const PROPERTY_ID = 'property-1';

    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;
    private CompletedPropertyFootprintFinder $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyCarbonFootprintRepository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->sut = new CompletedPropertyFootprintFinder($this->propertyCarbonFootprintRepository);
    }

    public function testFindsACompletedFootprintOfTheSameAccountAndYear(): void
    {
        $propertyFootprint = $this->propertyFootprint();
        $propertyFootprint->complete(new Emissions('100'));

        $this->assertSame($propertyFootprint, $this->sut->find($this->property(), $this->companyFootprint()));
    }

    public function testReturnsNullWithoutFootprint(): void
    {
        $this->assertNull($this->sut->find($this->property(), $this->companyFootprint()));
    }

    public function testReturnsNullForARemovedFootprint(): void
    {
        $propertyFootprint = $this->propertyFootprint();
        $propertyFootprint->complete(new Emissions('100'));
        $propertyFootprint->remove();

        $this->assertNull($this->sut->find($this->property(), $this->companyFootprint()));
    }

    public function testReturnsNullForANotCompletedFootprint(): void
    {
        $this->propertyFootprint();

        $this->assertNull($this->sut->find($this->property(), $this->companyFootprint()));
    }

    public function testReturnsNullForAFootprintOfAnotherAccount(): void
    {
        $propertyFootprint = $this->propertyFootprint('other-account');
        $propertyFootprint->complete(new Emissions('100'));

        $this->assertNull($this->sut->find($this->property(), $this->companyFootprint()));
    }

    private function property(): Property
    {
        return PropertyMother::fromNameAndCompanyIdAndIdAndRegime(self::PROPERTY_ID, new CompanyId(self::COMPANY_ID), new PropertyId(self::PROPERTY_ID), OperatingRegime::Owned);
    }

    private function propertyFootprint(string $accountId = self::ACCOUNT_ID): PropertyCarbonFootprint
    {
        $propertyFootprint = PropertyCarbonFootprintMother::withEmployeesAnswered(self::PROPERTY_ID, (string) PropertyCarbonFootprintId::generate(), $accountId);
        $this->propertyCarbonFootprintRepository->add($propertyFootprint);

        return $propertyFootprint;
    }

    private function companyFootprint(): CompanyCarbonFootprint
    {
        return CompanyCarbonFootprintMother::withEmployeesAnswered(self::COMPANY_ID, (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID);
    }
}
