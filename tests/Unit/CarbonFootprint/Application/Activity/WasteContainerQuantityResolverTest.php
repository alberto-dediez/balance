<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\WasteContainerQuantityResolver;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use PHPUnit\Framework\TestCase;

class WasteContainerQuantityResolverTest extends TestCase
{
    public function testResolveDerivesQuantityFromContainer(): void
    {
        $waste = WasteMother::from(name: 'waste-name', id: (string) WasteId::generate(), unit: Unit::Kilogram, density: 250.0);
        $repository = new InMemoryWasteRepository();
        $repository->add($waste);

        $resolver = new WasteContainerQuantityResolver($repository);

        $quantity = $resolver->resolve($waste->id(), new Quantity(2, Unit::CubicMeter), 50.0);

        $this->assertSame(250.0, $quantity->value());
        $this->assertSame(Unit::Kilogram, $quantity->unit());
    }

    public function testResolveThrowsWhenWasteNotFound(): void
    {
        $repository = new InMemoryWasteRepository();
        $resolver = new WasteContainerQuantityResolver($repository);

        $this->expectException(\InvalidArgumentException::class);

        $resolver->resolve(WasteId::generate(), new Quantity(2, Unit::CubicMeter), 50.0);
    }
}
