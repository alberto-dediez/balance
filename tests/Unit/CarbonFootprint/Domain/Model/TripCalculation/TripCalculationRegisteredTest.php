<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRegistered;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Domain\Model\Geo\GeoPoint;
use PHPUnit\Framework\TestCase;

class TripCalculationRegisteredTest extends TestCase
{
    public function testStoresTheDestinationOfTheCalculation(): void
    {
        $tripCalculation = TripCalculationMother::withDestination(
            (string) TripCalculationLinkId::generate(),
            new TripWaypoint('place-valencia', 'Valencia, España', new GeoPoint(39.47, -0.37)),
        );

        $this->assertSame('place-valencia', $tripCalculation->destinationPlaceReference());
        $this->assertSame('Valencia, España', $tripCalculation->destinationLabel());
        $this->assertSame(39.47, $tripCalculation->destinationLatitude());
        $this->assertSame(-0.37, $tripCalculation->destinationLongitude());

        $events = array_values(array_filter(
            $tripCalculation->recordedEvents(),
            static fn(object $event): bool => $event instanceof TripCalculationRegistered,
        ));
        $this->assertCount(1, $events);

        $rebuilt = TripCalculationRegistered::rebuildFromPayload($events[0]->id(), $events[0]->occurredOn(), $events[0]->payload());

        $this->assertEquals($events[0]->destination(), $rebuilt->destination());
    }

    public function testRebuildsPayloadsWithoutDestination(): void
    {
        $tripCalculation = TripCalculationMother::random();

        $events = array_values(array_filter(
            $tripCalculation->recordedEvents(),
            static fn(object $event): bool => $event instanceof TripCalculationRegistered,
        ));
        $payload = $events[0]->payload();
        unset($payload['destination']);

        $rebuilt = TripCalculationRegistered::rebuildFromPayload($events[0]->id(), $events[0]->occurredOn(), $payload);

        $this->assertNull($rebuilt->destination());
        $this->assertNull($tripCalculation->destinationLabel());
    }
}
