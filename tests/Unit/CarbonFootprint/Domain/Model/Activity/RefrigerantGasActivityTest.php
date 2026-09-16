<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityRemoved;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityUpdated;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RefrigerantGasActivityTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesRegisteredEvent(): void
    {
        $activity = RefrigerantGasActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(RefrigerantGasActivityRegistered::class, $event);
        $this->assertTrue($event->refrigerantGasId()->equals($activity->refrigerantGasId()));
        $this->assertSame('Sala de máquinas', $activity->source());
        $this->assertSame(2.0, $activity->quantity()->value());
        $this->assertSame(Unit::Kilogram, $activity->quantity()->unit());
        $this->assertEquals($event->occurredOn(), $activity->registeredAt());
    }

    public function testUpdatePublishesUpdatedEvent(): void
    {
        $activity = RefrigerantGasActivityMother::registered();
        $refrigerantGasId = RefrigerantGasId::generate();

        $activity->update(
            refrigerantGasId: $refrigerantGasId,
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Tonne, 3.0),
            source: 'Cubierta',
        );

        $this->assertInstanceOf(RefrigerantGasActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($refrigerantGasId->equals($activity->refrigerantGasId()));
        $this->assertSame(3.0, $activity->quantity()->value());
        $this->assertSame('Cubierta', $activity->source());
        $this->assertNotNull($activity->updatedAt());
    }

    public function testRemovePublishesRemovedEvent(): void
    {
        $activity = RefrigerantGasActivityMother::registered();

        $activity->remove();

        $this->assertInstanceOf(RefrigerantGasActivityRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisteredEventRebuildsFromPayload(): void
    {
        $activity = RefrigerantGasActivityMother::registered();
        $event = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(RefrigerantGasActivityRegistered::class, $event);

        $rebuilt = RefrigerantGasActivityRegistered::rebuildFromPayload(
            DomainEventId::generate(),
            $event->occurredOn(),
            $event->payload(),
        );

        $this->assertTrue($activity->refrigerantGasId()->equals($rebuilt->refrigerantGasId()));
        $this->assertSame(2.0, $rebuilt->quantity()->value());
        $this->assertSame(Unit::Kilogram, $rebuilt->quantity()->unit());
        $this->assertSame('Sala de máquinas', $rebuilt->source());
    }
}
