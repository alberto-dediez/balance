<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivityRemoved;
use App\CarbonFootprint\Domain\Model\Activity\UseProfileActivityUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UseProfileActivityTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $activity = UseProfileActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(UseProfileActivityRegistered::class, $event);
        $this->assertTrue($event->useProfileId()->equals($activity->useProfileId()));
        $this->assertSame(Unit::Piece, $activity->emissionFactor()->unit());
        $this->assertSame('15.0', $activity->emissions()->co2eq());
        $this->assertSame(100.0, $activity->quantity()->value());
        $this->assertSame(Unit::Piece, $activity->quantity()->unit());
        $this->assertEquals($event->occurredOn(), $activity->registeredAt());
    }

    public function testUpdatePublishesUpdatedEvent(): void
    {
        $activity = UseProfileActivityMother::registered();
        $useProfileId = UseProfileId::generate();

        $activity->update(
            useProfileId: $useProfileId,
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Piece),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Piece, 300.0),
        );

        $this->assertInstanceOf(UseProfileActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($useProfileId->equals($activity->useProfileId()));
        $this->assertSame(300.0, $activity->quantity()->value());
        $this->assertSame(Unit::Piece, $activity->quantity()->unit());
        $this->assertNotNull($activity->updatedAt());
    }

    public function testRemovePublishesRemovedEvent(): void
    {
        $activity = UseProfileActivityMother::registered();

        $activity->remove();

        $this->assertInstanceOf(UseProfileActivityRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisteredEventRebuildsFromPayload(): void
    {
        $activity = UseProfileActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(UseProfileActivityRegistered::class, $event);

        $rebuilt = UseProfileActivityRegistered::rebuildFromPayload(
            DomainEventId::generate(),
            $event->occurredOn(),
            $event->payload(),
        );

        $this->assertTrue($rebuilt->useProfileId()->equals($activity->useProfileId()));
        $this->assertSame(Unit::Piece, $rebuilt->quantity()->unit());
    }
}
