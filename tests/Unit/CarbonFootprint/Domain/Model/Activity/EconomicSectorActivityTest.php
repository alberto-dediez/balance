<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivityRemoved;
use App\CarbonFootprint\Domain\Model\Activity\EconomicSectorActivityUpdated;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class EconomicSectorActivityTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $activity = EconomicSectorActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(EconomicSectorActivityRegistered::class, $event);
        $this->assertTrue($event->economicSectorId()->equals($activity->economicSectorId()));
        $this->assertSame(Unit::Euro, $activity->emissionFactor()->unit());
        $this->assertSame('15.0', $activity->emissions()->co2eq());
        $this->assertSame(100.0, $activity->quantity()->value());
        $this->assertSame(Unit::Euro, $activity->quantity()->unit());
        $this->assertEquals($event->occurredOn(), $activity->registeredAt());
    }

    public function testUpdatePublishesUpdatedEvent(): void
    {
        $activity = EconomicSectorActivityMother::registered();
        $economicSectorId = EconomicSectorId::generate();

        $activity->update(
            economicSectorId: $economicSectorId,
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Euro),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Euro, 300.0),
        );

        $this->assertInstanceOf(EconomicSectorActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($economicSectorId->equals($activity->economicSectorId()));
        $this->assertSame(300.0, $activity->quantity()->value());
        $this->assertSame(Unit::Euro, $activity->quantity()->unit());
        $this->assertNotNull($activity->updatedAt());
    }

    public function testRemovePublishesRemovedEvent(): void
    {
        $activity = EconomicSectorActivityMother::registered();

        $activity->remove();

        $this->assertInstanceOf(EconomicSectorActivityRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisteredEventRebuildsFromPayload(): void
    {
        $activity = EconomicSectorActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(EconomicSectorActivityRegistered::class, $event);

        $rebuilt = EconomicSectorActivityRegistered::rebuildFromPayload(
            DomainEventId::generate(),
            $event->occurredOn(),
            $event->payload(),
        );

        $this->assertTrue($rebuilt->economicSectorId()->equals($activity->economicSectorId()));
        $this->assertSame(Unit::Euro, $rebuilt->quantity()->unit());
    }
}
