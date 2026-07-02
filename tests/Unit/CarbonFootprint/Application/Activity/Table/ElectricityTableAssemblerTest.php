<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Application\Activity\Table\ElectricityTableAssembler;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use PHPUnit\Framework\TestCase;

class ElectricityTableAssemblerTest extends TestCase
{
    private ElectricityTableAssembler $assembler;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->assembler = new ElectricityTableAssembler(ElectricityMarketerRepositoryMother::inMemory());
    }

    public function testActivityTypeIsElectricity(): void
    {
        $this->assertSame(ActivityType::Electricity, $this->assembler->activityType());
    }

    public function testLoadCatalogReturnsOnlyMarketersHavingEmissionFactorForTheCalculationYear(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $withFactor = ElectricityMarketerMother::registeredElectricityMarketer();
        $withFactor->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withYear($eventCarbonFootprint->calculationYear()));

        $withoutFactor = ElectricityMarketerMother::registeredElectricityMarketer();

        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $repository->add($withFactor);
        $repository->add($withoutFactor);

        $assembler = new ElectricityTableAssembler($repository);

        $catalog = $assembler->loadCatalog($eventCarbonFootprint);

        $this->assertCount(1, $catalog);
        $this->assertSame($withFactor, $catalog[0]);
    }

    public function testBuildRowsReturnsRowWithItemId2ForSavedElectricityActivities(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $activity = ElectricityActivityMother::registered();

        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $eventCarbonFootprint->addActivity($activity->id(), Phase::Assembly, Source::MachineryOperation);

        $rows = $this->assembler->buildRows($eventCarbonFootprint, $activityRepository, Source::MachineryOperation);

        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame((string) $activity->electricityMarketerId(), $row->itemId);
        $this->assertSame((string) $activity->gdOFactorId(), $row->itemId2);
        $this->assertNull($row->condition);
        $this->assertSame($activity->quantity()->unit()->value, $row->unit);
        $this->assertSame($activity->quantity()->value(), $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
    }

    public function testBuildRowsFiltersBySource(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $activity = ElectricityActivityMother::registered();

        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $eventCarbonFootprint->addActivity($activity->id(), Phase::Assembly, Source::BuildingElectricity);

        $rows = $this->assembler->buildRows($eventCarbonFootprint, $activityRepository, Source::MachineryOperation);

        $this->assertSame([], $rows);
    }
}
