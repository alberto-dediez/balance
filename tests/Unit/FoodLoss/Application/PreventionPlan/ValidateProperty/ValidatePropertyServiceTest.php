<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\ValidateProperty;

use App\FoodLoss\Application\PreventionPlan\ValidateProperty\ValidatePropertyRequest;
use App\FoodLoss\Application\PreventionPlan\ValidateProperty\ValidatePropertyService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryCriticalPointRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use PHPUnit\Framework\TestCase;

class ValidatePropertyServiceTest extends TestCase
{
    private PropertyRepository $propertyRepository;
    private CompanyRepository $companyRepository;
    private CriticalPointRepository $criticalPointRepository;

    protected function setUp(): void
    {
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->companyRepository = new InMemoryCompanyRepository();
        $this->criticalPointRepository = new InMemoryCriticalPointRepository();
    }

    public function testReturnsNoErrorsWhenPropertyNotFound(): void
    {
        $sut = $this->createSUT();

        $result = $sut(new ValidatePropertyRequest(propertyId: PropertyId::generate()));

        $this->assertFalse($result->hasErrors);
        $this->assertEmpty($result->errors);
    }

    public function testReturnsErrorWhenPropertyHasNoCompanyAssigned(): void
    {
        $property = PropertyMother::registeredProperty();
        $this->propertyRepository->add($property);

        $criticalPoint = CriticalPointMother::fromNameAndAccountAndProperty(
            'cp-1',
            AccountId::generate(),
            $property->id(),
        );
        $this->criticalPointRepository->add($criticalPoint);

        $sut = $this->createSUT();

        $result = $sut(new ValidatePropertyRequest(propertyId: $property->id()));

        $this->assertTrue($result->hasErrors);
        $this->assertCount(1, $result->errors);
        $this->assertEquals('Sin empresa asignada', (string) $result->errors[0]->getMessage());
    }

    public function testReturnsErrorWhenCompanyNotFound(): void
    {
        $companyId = CompanyId::generate();
        $property = PropertyMother::fromNameAndCompanyId('Hotel Test', $companyId);
        $this->propertyRepository->add($property);

        $criticalPoint = CriticalPointMother::fromNameAndAccountAndProperty(
            'cp-1',
            AccountId::generate(),
            $property->id(),
        );
        $this->criticalPointRepository->add($criticalPoint);

        $sut = $this->createSUT();

        $result = $sut(new ValidatePropertyRequest(propertyId: $property->id()));

        $this->assertTrue($result->hasErrors);
        $this->assertCount(1, $result->errors);
        $this->assertEquals('La empresa no tiene CIF', (string) $result->errors[0]->getMessage());
    }

    public function testReturnsErrorWhenCompanyHasNoVatNumber(): void
    {
        $company = CompanyMother::withoutVatNumber();
        $this->companyRepository->add($company);

        $property = PropertyMother::fromNameAndCompanyId('Hotel Test', $company->id());
        $this->propertyRepository->add($property);

        $criticalPoint = CriticalPointMother::fromNameAndAccountAndProperty(
            'cp-1',
            AccountId::generate(),
            $property->id(),
        );
        $this->criticalPointRepository->add($criticalPoint);

        $sut = $this->createSUT();

        $result = $sut(new ValidatePropertyRequest(propertyId: $property->id()));

        $this->assertTrue($result->hasErrors);
        $this->assertCount(1, $result->errors);
        $this->assertEquals('La empresa no tiene CIF', (string) $result->errors[0]->getMessage());
    }

    public function testReturnsErrorWhenNoCriticalPointsAssigned(): void
    {
        $company = CompanyMother::registeredCompany();
        $this->companyRepository->add($company);

        $property = PropertyMother::fromNameAndCompanyId('Hotel Test', $company->id());
        $this->propertyRepository->add($property);

        $sut = $this->createSUT();

        $result = $sut(new ValidatePropertyRequest(propertyId: $property->id()));

        $this->assertTrue($result->hasErrors);
        $this->assertCount(1, $result->errors);
        $this->assertEquals('Sin puntos críticos asignados', (string) $result->errors[0]->getMessage());
    }

    public function testReturnsNoErrorsWhenAllConditionsMet(): void
    {
        $company = CompanyMother::registeredCompany();
        $this->companyRepository->add($company);

        $property = PropertyMother::fromNameAndCompanyId('Hotel Test', $company->id());
        $this->propertyRepository->add($property);

        $criticalPoint = CriticalPointMother::fromNameAndAccountAndProperty(
            'cp-1',
            AccountId::generate(),
            $property->id(),
        );
        $this->criticalPointRepository->add($criticalPoint);

        $sut = $this->createSUT();

        $result = $sut(new ValidatePropertyRequest(propertyId: $property->id()));

        $this->assertFalse($result->hasErrors);
        $this->assertEmpty($result->errors);
        $this->assertEquals('Hotel Test', $result->propertyName);
    }

    public function testReturnsMultipleErrorsWhenBothCompanyAndCriticalPointsMissing(): void
    {
        $property = PropertyMother::registeredProperty();
        $this->propertyRepository->add($property);

        $sut = $this->createSUT();

        $result = $sut(new ValidatePropertyRequest(propertyId: $property->id()));

        $this->assertTrue($result->hasErrors);
        $this->assertCount(2, $result->errors);
    }

    private function createSUT(): ValidatePropertyService
    {
        return new ValidatePropertyService(
            propertyRepository: $this->propertyRepository,
            companyRepository: $this->companyRepository,
            criticalPointRepository: $this->criticalPointRepository,
        );
    }
}
