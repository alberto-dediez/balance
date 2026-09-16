<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use PHPUnit\Framework\TestCase;

class EmissionsBreakdownBuilderTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
    }

    public function testAggregatesThePropertyFootprintByScopeAndBySource(): void
    {
        $propertyCarbonFootprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $combustible = CombustibleActivityMother::registered();
        $electricity = ElectricityActivityMother::registered();
        $this->activityRepository->save($combustible);
        $this->activityRepository->save($electricity);

        $propertyCarbonFootprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyCarbonFootprint->addActivity($electricity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        $breakdown = $this->createSUT()->build($propertyCarbonFootprint, Scope::cases());

        $this->assertSame('30', $breakdown->total->co2eq());
        $this->assertSame('15', $breakdown->byStage[Scope::Scope1->key()]->co2eq());
        $this->assertSame('15', $breakdown->byStage[Scope::Scope2->key()]->co2eq());
        $this->assertSame('15', $breakdown->bySource[Source::FixedFacilities->value]->co2eq());
        $this->assertSame('15', $breakdown->byStageAndSource[Scope::Scope2->key()][Source::BuildingElectricity->value]->co2eq());
        $this->assertSame('0', $breakdown->byStageAndSource[Scope::Scope1->key()][Source::BuildingElectricity->value]->co2eq());
    }

    public function testAddsUpTheLocationBasedElectricityOnlyForActivitiesThatHaveIt(): void
    {
        $propertyCarbonFootprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $withLocationBased = ElectricityActivityMother::registeredWithLocationBasedResult();
        $withoutLocationBased = ElectricityActivityMother::registered();
        $this->activityRepository->save($withLocationBased);
        $this->activityRepository->save($withoutLocationBased);

        $propertyCarbonFootprint->addActivity($withLocationBased->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $propertyCarbonFootprint->addActivity($withoutLocationBased->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        $breakdown = $this->createSUT()->build($propertyCarbonFootprint, Scope::cases());

        $this->assertSame('180', $breakdown->locationBasedElectricity->co2eq());
    }

    public function testAggregatesTheCompanyFootprintByPropertyWithAnEmptyKeyForTheUnassignedBlock(): void
    {
        $companyCarbonFootprint = CompanyCarbonFootprintMother::from('C-1', (string) CompanyCarbonFootprintId::generate());

        $propertyId = new PropertyId('P-1');
        $assigned = CombustibleActivityMother::registered();
        $unassigned = CombustibleActivityMother::registered();
        $this->activityRepository->save($assigned);
        $this->activityRepository->save($unassigned);

        $companyCarbonFootprint->addActivity($assigned->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $propertyId));
        $companyCarbonFootprint->addActivity($unassigned->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $breakdown = $this->createSUT()->build($companyCarbonFootprint, Scope::cases());

        $this->assertSame('15', $breakdown->byPropertyId['P-1']->co2eq());
        $this->assertSame('15', $breakdown->byPropertyId['']->co2eq());
    }

    private function createSUT(): EmissionsBreakdownBuilder
    {
        return new EmissionsBreakdownBuilder($this->activityRepository);
    }
}
