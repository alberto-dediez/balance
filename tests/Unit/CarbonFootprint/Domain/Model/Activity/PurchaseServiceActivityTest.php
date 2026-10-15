<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityRemoved;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityUpdated;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class PurchaseServiceActivityTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $activity = PurchaseServiceActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(PurchaseServiceActivityRegistered::class, $event);
        $this->assertTrue($event->purchaseServiceId()->equals($activity->purchaseServiceId()));
        $this->assertSame(Unit::Euro, $activity->emissionFactor()->unit());
        $this->assertSame('15.0', $activity->emissions()->co2eq());
        $this->assertSame(100.0, $activity->quantity()->value());
        $this->assertSame(Unit::Euro, $activity->quantity()->unit());
        $this->assertEquals($event->occurredOn(), $activity->registeredAt());
    }

    public function testUpdatePublishesUpdatedEvent(): void
    {
        $activity = PurchaseServiceActivityMother::registered();
        $purchaseServiceId = PurchaseServiceId::generate();

        $activity->update(
            purchaseServiceId: $purchaseServiceId,
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::Euro),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::UsDollar, 300.0),
        );

        $this->assertInstanceOf(PurchaseServiceActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($purchaseServiceId->equals($activity->purchaseServiceId()));
        $this->assertSame(300.0, $activity->quantity()->value());
        $this->assertSame(Unit::UsDollar, $activity->quantity()->unit());
        $this->assertNotNull($activity->updatedAt());
    }

    public function testRemovePublishesRemovedEvent(): void
    {
        $activity = PurchaseServiceActivityMother::registered();

        $activity->remove();

        $this->assertInstanceOf(PurchaseServiceActivityRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisteredEventRebuildsFromPayload(): void
    {
        $activity = PurchaseServiceActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(PurchaseServiceActivityRegistered::class, $event);

        $rebuilt = PurchaseServiceActivityRegistered::rebuildFromPayload(
            DomainEventId::generate(),
            $event->occurredOn(),
            $event->payload(),
        );

        $this->assertTrue($rebuilt->purchaseServiceId()->equals($activity->purchaseServiceId()));
        $this->assertSame(Unit::Euro, $rebuilt->quantity()->unit());
    }
}
