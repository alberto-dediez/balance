<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
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

    public function testAddsUpTheMarketBasedElectricityOfEveryElectricityActivity(): void
    {
        $propertyCarbonFootprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $withLocationBased = ElectricityActivityMother::registeredWithLocationBasedResult();
        $withoutLocationBased = ElectricityActivityMother::registered();
        $this->activityRepository->save($withLocationBased);
        $this->activityRepository->save($withoutLocationBased);

        $propertyCarbonFootprint->addActivity($withLocationBased->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $propertyCarbonFootprint->addActivity($withoutLocationBased->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        $breakdown = $this->createSUT()->build($propertyCarbonFootprint, Scope::cases());

        $this->assertSame('30', $breakdown->marketBasedElectricity->co2eq());
        $this->assertSame(2, $breakdown->electricityActivities);
        $this->assertSame(1, $breakdown->electricityActivitiesWithoutLocationBased);
    }

    public function testTheLocationBasedTotalReplacesOnlyTheElectricityThatHasALocationBasedResult(): void
    {
        $propertyCarbonFootprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $combustible = CombustibleActivityMother::registered();
        $withLocationBased = ElectricityActivityMother::registeredWithLocationBasedResult();
        $withoutLocationBased = ElectricityActivityMother::registered();
        $this->activityRepository->save($combustible);
        $this->activityRepository->save($withLocationBased);
        $this->activityRepository->save($withoutLocationBased);

        $propertyCarbonFootprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyCarbonFootprint->addActivity($withLocationBased->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $propertyCarbonFootprint->addActivity($withoutLocationBased->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        $breakdown = $this->createSUT()->build($propertyCarbonFootprint, Scope::cases());

        $this->assertSame('45', $breakdown->total->co2eq());
        $this->assertSame((float) $breakdown->total->co2eq() - 15.0 + 180.0, (float) $breakdown->locationBasedTotal->co2eq());
    }

    public function testTheLocationBasedTotalEqualsTheTotalWithoutElectricityActivities(): void
    {
        $propertyCarbonFootprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $combustible = CombustibleActivityMother::registered();
        $this->activityRepository->save($combustible);
        $propertyCarbonFootprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $breakdown = $this->createSUT()->build($propertyCarbonFootprint, Scope::cases());

        $this->assertTrue($breakdown->locationBasedTotal->equals($breakdown->total));
        $this->assertSame('0', $breakdown->marketBasedElectricity->co2eq());
        $this->assertSame(0, $breakdown->electricityActivities);
        $this->assertSame(0, $breakdown->electricityActivitiesWithoutLocationBased);
    }

    public function testTheLocationBasedTotalIncludesTheDerivedScope3Lines(): void
    {
        $propertyCarbonFootprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $combustible = CombustibleActivityMother::registeredWithWellToTank();
        $purchase = PurchaseServiceActivityMother::registered();
        $this->activityRepository->save($combustible);
        $this->activityRepository->save($purchase);

        $propertyCarbonFootprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyCarbonFootprint->addActivity($purchase->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));

        $breakdown = $this->createSUT()->build($propertyCarbonFootprint, Scope::cases());

        $expectedTotal = (float) $combustible->emissions()->co2eq() + (float) $purchase->emissions()->co2eq() + 122.0;
        $this->assertSame($expectedTotal, (float) $breakdown->locationBasedTotal->co2eq());
    }

    public function testCountsTheMachineryElectricityOfAnEventAsElectricity(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $electricity = ElectricityActivityMother::registeredWithLocationBasedResult();
        $this->activityRepository->save($electricity);
        $eventCarbonFootprint->addActivity($electricity->id(), new ActivityPlacement(Phase::Assembly, Source::MachineryOperation));

        $breakdown = $this->createSUT()->build($eventCarbonFootprint, Phase::cases());

        $this->assertSame(1, $breakdown->electricityActivities);
        $this->assertSame('180', $breakdown->locationBasedElectricity->co2eq());
    }

    public function testTheMarketBasedElectricityMatchesScope2InAPropertyFootprint(): void
    {
        $propertyCarbonFootprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $combustible = CombustibleActivityMother::registered();
        $withLocationBased = ElectricityActivityMother::registeredWithLocationBasedResult();
        $withoutLocationBased = ElectricityActivityMother::registered();
        $this->activityRepository->save($combustible);
        $this->activityRepository->save($withLocationBased);
        $this->activityRepository->save($withoutLocationBased);

        $propertyCarbonFootprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyCarbonFootprint->addActivity($withLocationBased->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $propertyCarbonFootprint->addActivity($withoutLocationBased->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        $breakdown = $this->createSUT()->build($propertyCarbonFootprint, Scope::cases());

        $this->assertTrue($breakdown->marketBasedElectricity->equals($breakdown->byStage[Scope::Scope2->key()]));
    }

    public function testIncludesTheDerivedFuelAndEnergyRelatedEmissionsInScope3(): void
    {
        $propertyCarbonFootprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $combustible = CombustibleActivityMother::registeredWithWellToTank();
        $purchase = PurchaseServiceActivityMother::registered();
        $this->activityRepository->save($combustible);
        $this->activityRepository->save($purchase);

        $propertyCarbonFootprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyCarbonFootprint->addActivity($purchase->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));

        $breakdown = $this->createSUT()->build($propertyCarbonFootprint, Scope::cases());

        $expectedTotal = (float) $combustible->emissions()->co2eq() + (float) $purchase->emissions()->co2eq() + 122.0;
        $this->assertSame($expectedTotal, (float) $breakdown->total->co2eq());
        $this->assertSame('122', $breakdown->byStageAndSource[Scope::Scope3->key()][Source::FuelAndEnergyRelated->value]->co2eq());
        $this->assertSame('122', $breakdown->bySource[Source::FuelAndEnergyRelated->value]->co2eq());
        $this->assertSame((float) $purchase->emissions()->co2eq() + 122.0, (float) $breakdown->byStage[Scope::Scope3->key()]->co2eq());
    }

    public function testDoesNotAddDerivedEmissionsWhenTheScopeIsExcluded(): void
    {
        $propertyCarbonFootprint = PropertyCarbonFootprintMother::withScope2Answered('P-1', (string) PropertyCarbonFootprintId::generate());

        $combustible = CombustibleActivityMother::registeredWithWellToTank();
        $this->activityRepository->save($combustible);
        $propertyCarbonFootprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyCarbonFootprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $breakdown = $this->createSUT()->build($propertyCarbonFootprint, Scope::cases());

        $this->assertSame('15', $breakdown->total->co2eq());
        $this->assertSame('0', $breakdown->byStage[Scope::Scope3->key()]->co2eq());
    }

    public function testDoesNotAddDerivedEmissionsForPhases(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $combustible = CombustibleActivityMother::registeredWithWellToTank();
        $this->activityRepository->save($combustible);
        $eventCarbonFootprint->addActivity($combustible->id(), new ActivityPlacement(Phase::Assembly, Source::FixedFacilities));

        $breakdown = $this->createSUT()->build($eventCarbonFootprint, Phase::cases());

        $this->assertSame('15', $breakdown->total->co2eq());
        $this->assertSame('0', $breakdown->bySource[Source::FuelAndEnergyRelated->value]->co2eq());
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

    public function testDistributesTheDerivedEmissionsByThePropertyOfTheirOriginActivity(): void
    {
        $companyCarbonFootprint = CompanyCarbonFootprintMother::from('C-1', (string) CompanyCarbonFootprintId::generate());

        $firstProperty = CombustibleActivityMother::registeredWithWellToTank();
        $secondProperty = CombustibleActivityMother::registeredWithWellToTank();
        $unassigned = CombustibleActivityMother::registeredWithWellToTank();
        $this->activityRepository->save($firstProperty);
        $this->activityRepository->save($secondProperty);
        $this->activityRepository->save($unassigned);

        $companyCarbonFootprint->addActivity($firstProperty->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: new PropertyId('P-1')));
        $companyCarbonFootprint->addActivity($secondProperty->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: new PropertyId('P-2')));
        $companyCarbonFootprint->addActivity($unassigned->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $breakdown = $this->createSUT()->build($companyCarbonFootprint, Scope::cases());

        $this->assertSame(137.0, (float) $breakdown->byPropertyId['P-1']->co2eq());
        $this->assertSame(137.0, (float) $breakdown->byPropertyId['P-2']->co2eq());
        $this->assertSame(137.0, (float) $breakdown->byPropertyId['']->co2eq());
        $this->assertSame(
            (float) $breakdown->total->co2eq(),
            array_sum(array_map(static fn($emissions): float => (float) $emissions->co2eq(), $breakdown->byPropertyId)),
        );
    }

    public function testIncludesTheNonConsolidatedPropertiesOfACompanyFootprint(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('franchised-1'), Source::Franchises, new Emissions('5000', '5000'));
        $footprint->recordNonConsolidatedProperty(new PropertyId('leased-out-1'), Source::DownstreamLeasedAssets, null, new Emissions('300'), '4500');

        $combustible = CombustibleActivityMother::registered();
        $this->activityRepository->save($combustible);
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));

        $breakdown = $this->createSUT()->build($footprint, Scope::cases());

        $this->assertSame(9515.0, (float) $breakdown->total->co2eq());
        $this->assertSame(9515.0, (float) $breakdown->byStage[Scope::Scope3->key()]->co2eq());
        $this->assertSame(5000.0, (float) $breakdown->byStageAndSource[Scope::Scope3->key()][Source::Franchises->value]->co2eq());
        $this->assertSame(4500.0, (float) $breakdown->byStageAndSource[Scope::Scope3->key()][Source::DownstreamLeasedAssets->value]->co2eq());
        $this->assertSame(4500.0, (float) $breakdown->bySource[Source::DownstreamLeasedAssets->value]->co2eq());
        $this->assertSame(5000.0, (float) $breakdown->byPropertyId['franchised-1']->co2eq());
        $this->assertSame(4500.0, (float) $breakdown->byPropertyId['leased-out-1']->co2eq());
    }

    private function createSUT(): EmissionsBreakdownBuilder
    {
        return new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository));
    }
}
