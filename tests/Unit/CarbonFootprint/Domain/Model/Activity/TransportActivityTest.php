<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\TransportActivityRegistered;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivityUpdated;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class TransportActivityTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterWithWeightAndDistanceExposesThem(): void
    {
        $activity = TransportActivityMother::registeredInTonneKilometre();

        $this->assertInstanceOf(TransportActivityRegistered::class, $this->spySubscriber->lastEvent());
        $weight = $activity->weight();
        $this->assertNotNull($weight);
        $this->assertSame(2500.0, $weight->value());
        $this->assertSame(Unit::Kilogram, $weight->unit());
        $this->assertSame(350.0, $activity->distanceKm());
        $this->assertSame(875.0, $activity->quantity()->value());
        $this->assertSame(Unit::TonneKilometre, $activity->quantity()->unit());
    }

    public function testRegisterWithoutWeightAndDistanceReturnsNulls(): void
    {
        $activity = TransportActivityMother::registered();

        $this->assertNull($activity->weight());
        $this->assertNull($activity->distanceKm());
    }

    public function testUpdateReplacesWeightAndDistance(): void
    {
        $activity = TransportActivityMother::registeredInTonneKilometre();
        $transportId = TransportId::generate();

        $activity->update(
            transportId: $transportId,
            emissionFactor: ActivityMother::emissionFactor()->withUnit(Unit::TonneKilometre),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::TonneKilometre, 1000.0),
            weight: new Quantity(4.0, Unit::Tonne),
            distanceKm: 250.0,
        );

        $this->assertInstanceOf(TransportActivityUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($transportId->equals($activity->transportId()));
        $weight = $activity->weight();
        $this->assertNotNull($weight);
        $this->assertSame(4.0, $weight->value());
        $this->assertSame(Unit::Tonne, $weight->unit());
        $this->assertSame(250.0, $activity->distanceKm());
        $this->assertNotNull($activity->updatedAt());
    }

    public function testUpdateWithoutWeightAndDistanceClearsThem(): void
    {
        $activity = TransportActivityMother::registeredInTonneKilometre();

        $activity->update(
            transportId: $activity->transportId(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Kilogram, 100.0),
        );

        $this->assertNull($activity->weight());
        $this->assertNull($activity->distanceKm());
    }
}
