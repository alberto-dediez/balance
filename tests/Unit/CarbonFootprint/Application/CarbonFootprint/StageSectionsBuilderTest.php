<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ElectricityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\RefrigerantGasCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\TransportCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use PHPUnit\Framework\TestCase;

class StageSectionsBuilderTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
    }

    public function testReturnsOneSectionPerStageSectionDefinition(): void
    {
        $sections = $this->createSUT()->build($this->propertyFootprint(), Scope::Scope1);

        $this->assertCount(count(Scope::Scope1->sections()), $sections->sections);
        foreach (Scope::Scope1->sections() as $index => $definition) {
            $this->assertSame($definition->key, $sections->sections[$index]->definition->key);
        }
    }

    public function testEachSectionOnlyCarriesTheActivitiesOfItsOwnScopeAndSource(): void
    {
        $propertyCarbonFootprint = $this->propertyFootprint();

        $combustible = CombustibleActivityMother::registered();
        $electricity = ElectricityActivityMother::registered();
        $this->activityRepository->save($combustible);
        $this->activityRepository->save($electricity);

        $propertyCarbonFootprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyCarbonFootprint->addActivity($electricity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        $scope1 = $this->createSUT()->build($propertyCarbonFootprint, Scope::Scope1);
        $this->assertCount(1, $scope1->sections[0]->activities);
        $this->assertSame($combustible, $scope1->sections[0]->activities[0]);

        $scope2 = $this->createSUT()->build($propertyCarbonFootprint, Scope::Scope2);
        $this->assertCount(1, $scope2->sections[0]->activities);
        $this->assertSame($electricity, $scope2->sections[0]->activities[0]);
    }

    public function testExposesTheExclusionsOfTheStage(): void
    {
        $propertyCarbonFootprint = $this->propertyFootprint();
        $propertyCarbonFootprint->excludeSource(Scope::Scope1, Source::OwnVehicles, ExclusionReason::NotApplicable);

        $sections = $this->createSUT()->build($propertyCarbonFootprint, Scope::Scope1);

        $this->assertArrayHasKey(Source::OwnVehicles->value, $sections->exclusions);
        $this->assertArrayNotHasKey(Source::FixedFacilities->value, $sections->exclusions);
    }

    public function testACompanyBlockOnlyListsTheActivitiesOfItsOwnProperty(): void
    {
        $companyCarbonFootprint = CompanyCarbonFootprintMother::from('C-1', (string) CompanyCarbonFootprintId::generate());

        $propertyId = new PropertyId('P-1');
        $assigned = CombustibleActivityMother::registered();
        $unassigned = CombustibleActivityMother::registered();
        $this->activityRepository->save($assigned);
        $this->activityRepository->save($unassigned);

        $companyCarbonFootprint->addActivity($assigned->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $propertyId));
        $companyCarbonFootprint->addActivity($unassigned->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $propertyBlock = $this->createSUT()->build($companyCarbonFootprint, Scope::Scope1, null, null, false, $propertyId);
        $this->assertCount(1, $propertyBlock->sections[0]->activities);
        $this->assertSame($assigned, $propertyBlock->sections[0]->activities[0]);

        $unassignedBlock = $this->createSUT()->build($companyCarbonFootprint, Scope::Scope1);
        $this->assertCount(1, $unassignedBlock->sections[0]->activities);
        $this->assertSame($unassigned, $unassignedBlock->sections[0]->activities[0]);
    }

    private function propertyFootprint(): PropertyCarbonFootprint
    {
        return PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());
    }

    private function createSUT(): StageSectionsBuilder
    {
        $catalogProviders = new ActivityCatalogProviderRegistry([
            new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
            new TransportCatalogProvider(TransportRepositoryMother::inMemory()),
            new RefrigerantGasCatalogProvider(RefrigerantGasRepositoryMother::inMemory()),
            new ElectricityCatalogProvider(ElectricityMarketerRepositoryMother::inMemory(), GdOFactorRepositoryMother::inMemory()),
        ]);

        return new StageSectionsBuilder($catalogProviders, $this->activityRepository);
    }
}
