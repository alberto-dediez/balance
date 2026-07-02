<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Application\Activity\Table\MaterialTableAssembler;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use PHPUnit\Framework\TestCase;

class MaterialTableAssemblerTest extends TestCase
{
    private MaterialTableAssembler $assembler;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->assembler = new MaterialTableAssembler(MaterialRepositoryMother::inMemory());
    }

    public function testActivityTypeIsMaterial(): void
    {
        $this->assertSame(ActivityType::Material, $this->assembler->activityType());
    }

    public function testLoadCatalogReturnsOnlyMaterialsHavingEmissionFactorForTheCalculationYear(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $withFactor = MaterialMother::registeredMaterial();
        $withFactor->setEmissionFactor(
            ActivityMother::emissionFactor($groupId)->withYear($eventCarbonFootprint->calculationYear()),
            ActivityMother::emissionFactor($groupId)->withYear($eventCarbonFootprint->calculationYear()),
        );

        $withoutFactor = MaterialMother::registeredMaterial();

        $repository = MaterialRepositoryMother::inMemory();
        $repository->add($withFactor);
        $repository->add($withoutFactor);

        $assembler = new MaterialTableAssembler($repository);

        $catalog = $assembler->loadCatalog($eventCarbonFootprint);

        $this->assertCount(1, $catalog);
        $this->assertSame($withFactor, $catalog[0]);
    }

    public function testLoadCatalogReturnsEmptyWhenNoEmissionFactorGroupIsConfigured(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId('event-id');

        $this->assertSame([], $this->assembler->loadCatalog($eventCarbonFootprint));
    }

    public function testBuildRowsReturnsActivityTableRowForSavedMaterialActivities(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $activity = MaterialActivityMother::registered();

        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $eventCarbonFootprint->addActivity($activity->id(), Phase::Materials, Source::Materials);

        $rows = $this->assembler->buildRows($eventCarbonFootprint, $activityRepository);

        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame((string) $activity->materialId(), $row->itemId);
        $this->assertSame($activity->condition()->value, $row->condition);
        $this->assertSame($activity->quantity()->unit()->value, $row->unit);
        $this->assertSame($activity->quantity()->value(), $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
        $this->assertNull($row->itemId2);
    }

    public function testBuildRowsIgnoresActivitiesFromOtherSources(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $activity = MaterialActivityMother::registered();

        $activityRepository = ActivityRepositoryMother::inMemory();
        $activityRepository->save($activity);

        $eventCarbonFootprint->addActivity($activity->id(), Phase::Assembly, Source::FixedFacilities);

        $rows = $this->assembler->buildRows($eventCarbonFootprint, $activityRepository);

        $this->assertSame([], $rows);
    }
}
