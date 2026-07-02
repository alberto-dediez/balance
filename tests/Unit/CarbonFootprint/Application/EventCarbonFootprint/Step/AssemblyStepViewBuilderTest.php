<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\Activity\Table\ActivityTableAssemblerRegistry;
use App\CarbonFootprint\Application\Activity\Table\CombustibleTableAssembler;
use App\CarbonFootprint\Application\Activity\Table\ElectricityTableAssembler;
use App\CarbonFootprint\Application\Activity\Table\MachineryFuelTableAssembler;
use App\CarbonFootprint\Application\Activity\Table\MobilityTableAssembler;
use App\CarbonFootprint\Application\Activity\Table\OvernightStayTableAssembler;
use App\CarbonFootprint\Application\Activity\Table\RoadTransportTableAssembler;
use App\CarbonFootprint\Application\Activity\Table\WasteTableAssembler;
use App\CarbonFootprint\Application\EventCarbonFootprint\Step\AssemblyStepViewBuilder;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use PHPUnit\Framework\TestCase;

class AssemblyStepViewBuilderTest extends TestCase
{
    private AssemblyStepViewBuilder $builder;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $registry = new ActivityTableAssemblerRegistry([
            new CombustibleTableAssembler(CombustibleRepositoryMother::inMemory()),
            new RoadTransportTableAssembler(RoadTransportRepositoryMother::inMemory()),
            new MachineryFuelTableAssembler(MachineryFuelRepositoryMother::inMemory()),
            new ElectricityTableAssembler(ElectricityMarketerRepositoryMother::inMemory()),
            new MobilityTableAssembler(MobilityRepositoryMother::inMemory()),
            new OvernightStayTableAssembler(OvernightStayRepositoryMother::inMemory()),
            new WasteTableAssembler(WasteRepositoryMother::inMemory()),
        ]);

        $this->builder = new AssemblyStepViewBuilder(
            $registry,
            ActivityRepositoryMother::inMemory(),
            GdOFactorRepositoryMother::inMemory(),
        );
    }

    public function testBuildReturnsAllKeysConsumedByTheTemplate(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $view = $this->builder->build($eventCarbonFootprint);

        $this->assertSame($eventCarbonFootprint, $view['eventCarbonFootprint']);
        $this->assertArrayHasKey('combustibles', $view);
        $this->assertArrayHasKey('roadTransports', $view);
        $this->assertArrayHasKey('machineryFuels', $view);
        $this->assertArrayHasKey('electricityMarketers', $view);
        $this->assertArrayHasKey('gdOFactors', $view);
        $this->assertArrayHasKey('mobilities', $view);
        $this->assertArrayHasKey('overnightStays', $view);
        $this->assertArrayHasKey('wastes', $view);
        $this->assertSame(Unit::cases(), $view['units']);
        $this->assertArrayHasKey('combustibleRows', $view);
        $this->assertArrayHasKey('roadTransportRows', $view);
        $this->assertArrayHasKey('machineryFuelRows', $view);
        $this->assertArrayHasKey('machineryElectricityRows', $view);
        $this->assertArrayHasKey('buildingElectricityRows', $view);
        $this->assertArrayHasKey('overnightStayRows', $view);
        $this->assertArrayHasKey('mobilityRows', $view);
        $this->assertArrayHasKey('wasteRows', $view);
    }
}
