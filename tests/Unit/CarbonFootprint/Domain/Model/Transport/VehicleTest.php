<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\Model\Transport\TransportMode;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use PHPUnit\Framework\TestCase;

class VehicleTest extends TestCase
{
    public function testModeOfEachVehicle(): void
    {
        $this->assertSame(TransportMode::Road, Vehicle::PassengerCars->mode());
        $this->assertSame(TransportMode::Road, Vehicle::Vans->mode());
        $this->assertSame(TransportMode::Road, Vehicle::Trucks->mode());
        $this->assertSame(TransportMode::Road, Vehicle::Mopeds->mode());
        $this->assertSame(TransportMode::Road, Vehicle::Motorcycles->mode());
        $this->assertSame(TransportMode::Road, Vehicle::Buses->mode());
        $this->assertSame(TransportMode::Rail, Vehicle::FreightTrain->mode());
        $this->assertSame(TransportMode::Sea, Vehicle::ContainerShip->mode());
        $this->assertSame(TransportMode::Sea, Vehicle::Barge->mode());
        $this->assertSame(TransportMode::Air, Vehicle::AirFreight->mode());
    }

    public function testOfModeRoadReturnsTheSixRoadVehicles(): void
    {
        $this->assertSame(
            [Vehicle::PassengerCars, Vehicle::Vans, Vehicle::Trucks, Vehicle::Mopeds, Vehicle::Motorcycles, Vehicle::Buses],
            Vehicle::ofMode(TransportMode::Road),
        );
    }

    public function testOfModeSeaReturnsContainerShipAndBarge(): void
    {
        $this->assertSame([Vehicle::ContainerShip, Vehicle::Barge], Vehicle::ofMode(TransportMode::Sea));
    }

    public function testOfModeCoversEveryVehicle(): void
    {
        $vehicles = [];
        foreach (TransportMode::cases() as $mode) {
            $vehicles = [...$vehicles, ...Vehicle::ofMode($mode)];
        }

        $this->assertEqualsCanonicalizing(Vehicle::cases(), $vehicles);
    }
}
