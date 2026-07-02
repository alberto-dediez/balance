<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Application\Activity\Table\ActivityTableAssembler;
use App\CarbonFootprint\Application\Activity\Table\ActivityTableAssemblerRegistry;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use PHPUnit\Framework\TestCase;

class ActivityTableAssemblerRegistryTest extends TestCase
{
    public function testResolvesAssemblerByActivityType(): void
    {
        $material = $this->stubAssembler(ActivityType::Material);
        $combustible = $this->stubAssembler(ActivityType::Combustible);

        $registry = new ActivityTableAssemblerRegistry([$material, $combustible]);

        $this->assertSame($material, $registry->forActivityType(ActivityType::Material));
        $this->assertSame($combustible, $registry->forActivityType(ActivityType::Combustible));
    }

    public function testThrowsForUnregisteredActivityType(): void
    {
        $registry = new ActivityTableAssemblerRegistry([]);

        $this->expectException(\InvalidArgumentException::class);
        $registry->forActivityType(ActivityType::Waste);
    }

    private function stubAssembler(ActivityType $type): ActivityTableAssembler
    {
        $assembler = $this->createStub(ActivityTableAssembler::class);
        $assembler->method('activityType')->willReturn($type);

        return $assembler;
    }
}
