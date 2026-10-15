<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ProcessActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\ProcessActivityRemoved;
use App\CarbonFootprint\Domain\Model\Activity\ProcessActivityUpdated;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ProcessActivityTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $activity = ProcessActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(ProcessActivityRegistered::class, $event);
        $this->assertTrue($event->processId()->equals($activity->processId()));
        $this->assertSame(Unit::Piece, $activity->emissionFactor()->unit());
        $this->assertSame('15.0', $activity->emissions()->co2eq());
        $this->assertSame(100.0, $activity->quantity()->value());
        $this->assertSame(Unit::Piece, $activity->quantity()->unit());
        $this->assertEquals($event->occurredOn(), $activity->registeredAt());
    }

    public function testUpdatePublishesUpdatedEvent(): void
    {
        $activity = ProcessActivityMother::registered();
        $processId = ProcessId::generate();

        $activity->update(
            processId: $processId,
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Piece),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Piece, 300.0),
        );

        $this->assertInstanceOf(ProcessActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($processId->equals($activity->processId()));
        $this->assertSame(300.0, $activity->quantity()->value());
        $this->assertSame(Unit::Piece, $activity->quantity()->unit());
        $this->assertNotNull($activity->updatedAt());
    }

    public function testRemovePublishesRemovedEvent(): void
    {
        $activity = ProcessActivityMother::registered();

        $activity->remove();

        $this->assertInstanceOf(ProcessActivityRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisteredEventRebuildsFromPayload(): void
    {
        $activity = ProcessActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(ProcessActivityRegistered::class, $event);

        $rebuilt = ProcessActivityRegistered::rebuildFromPayload(
            DomainEventId::generate(),
            $event->occurredOn(),
            $event->payload(),
        );

        $this->assertTrue($rebuilt->processId()->equals($activity->processId()));
        $this->assertSame(Unit::Piece, $rebuilt->quantity()->unit());
    }
}
