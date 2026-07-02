<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Sync\ActivityRow;
use App\CarbonFootprint\Application\Activity\Sync\ActivityRowHandler;
use App\CarbonFootprint\Application\Activity\Sync\ActivityRowHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Sync\PhaseActivitiesSynchronizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PhaseActivitiesSynchronizerTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegistersNewRowsAndAddsTheResultingActivityToThePhase(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $registeredActivityId = ActivityId::generate();

        $handler = $this->materialHandler();
        $handler->expects($this->once())
            ->method('register')
            ->with(
                $this->isInstanceOf(ActivityRow::class),
                $eventCarbonFootprint->calculationYear(),
                $eventCarbonFootprint->emissionFactorGroupId(),
                $this->isInstanceOf(User::class),
            )
            ->willReturn($registeredActivityId);
        $handler->expects($this->never())->method('update');

        $sut = $this->createSUT($handler);

        $sut->sync($eventCarbonFootprint, Phase::Materials, Source::Materials, [$this->materialRow()], UserMother::ownerUser());

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($registeredActivityId));
    }

    public function testUpdatesRowsThatMatchAnExistingActivityWithoutAddingDuplicates(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $existingActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($existingActivityId, Phase::Materials, Source::Materials);

        $handler = $this->materialHandler();
        $handler->expects($this->once())
            ->method('update')
            ->with($existingActivityId, $this->isInstanceOf(ActivityRow::class), $eventCarbonFootprint->calculationYear(), $eventCarbonFootprint->emissionFactorGroupId(), $this->isInstanceOf(User::class));
        $handler->expects($this->never())->method('register');

        $sut = $this->createSUT($handler);

        $sut->sync($eventCarbonFootprint, Phase::Materials, Source::Materials, [$this->materialRow($existingActivityId)], UserMother::ownerUser());

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($existingActivityId));
    }

    public function testRemovesActivitiesFromThePhaseAndSourceThatAreNoLongerPresentInTheRows(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $staleActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($staleActivityId, Phase::Materials, Source::Materials);

        $sut = $this->createSUT();

        $sut->sync($eventCarbonFootprint, Phase::Materials, Source::Materials, [], UserMother::ownerUser());

        $this->assertCount(0, $eventCarbonFootprint->activities()->activityIds());
    }

    public function testDoesNotTouchActivitiesFromOtherPhasesOrSources(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $otherPhaseActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($otherPhaseActivityId, Phase::Assembly, Source::MachineryOperation);

        $sut = $this->createSUT();

        $sut->sync($eventCarbonFootprint, Phase::Materials, Source::Materials, [], UserMother::ownerUser());

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($otherPhaseActivityId));
    }

    public function testThrowsWhenEventCarbonFootprintHasNoEmissionFactorGroupConfigured(): void
    {
        $eventCarbonFootprint = new EventCarbonFootprint(id: EventCarbonFootprintId::generate());

        $sut = $this->createSUT();

        $this->expectException(\InvalidArgumentException::class);
        $sut->sync($eventCarbonFootprint, Phase::Materials, Source::Materials, [$this->materialRow()], UserMother::ownerUser());
    }

    private function materialRow(?ActivityId $activityId = null): ActivityRow
    {
        return new ActivityRow(
            activityType: ActivityType::Material,
            itemId: 'material-id',
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
            activityId: $activityId,
        );
    }

    private function materialHandler(): ActivityRowHandler&MockObject
    {
        $handler = $this->createMock(ActivityRowHandler::class);
        $handler->method('activityType')->willReturn(ActivityType::Material);

        return $handler;
    }

    private function createSUT(?ActivityRowHandler $handler = null): PhaseActivitiesSynchronizer
    {
        $handlers = null !== $handler ? [$handler] : [];

        return new PhaseActivitiesSynchronizer(new ActivityRowHandlerRegistry($handlers));
    }
}
