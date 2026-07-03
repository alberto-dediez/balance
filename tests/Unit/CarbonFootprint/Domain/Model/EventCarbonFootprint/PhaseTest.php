<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use PHPUnit\Framework\TestCase;

class PhaseTest extends TestCase
{
    public function testMaterialsSections(): void
    {
        $sections = Phase::Materials->sections();

        $this->assertCount(3, $sections);
        $this->assertSame(['materials', 'cateringItems', 'merchandisingItems'], array_map(static fn($s) => $s->key, $sections));

        foreach ($sections as $section) {
            $this->assertSame(Source::Materials, $section->source);
        }

        $this->assertSame(ActivityType::Material, $sections[0]->activityType);
        $this->assertSame(ActivityType::CateringItem, $sections[1]->activityType);
        $this->assertSame(ActivityType::MerchandisingItem, $sections[2]->activityType);
    }

    public function testAssemblySections(): void
    {
        $sections = Phase::Assembly->sections();

        $this->assertCount(8, $sections);
        $this->assertSame(
            ['combustibles', 'roadTransports', 'machineryFuels', 'machineryElectricity', 'buildingElectricity', 'overnightStays', 'mobilities', 'wastes'],
            array_map(static fn($s) => $s->key, $sections),
        );
    }

    public function testMachineryFuelsAndMachineryElectricityShareSource(): void
    {
        $sections = Phase::Assembly->sections();

        $machineryFuels = array_values(array_filter($sections, static fn($s) => 'machineryFuels' === $s->key))[0];
        $machineryElectricity = array_values(array_filter($sections, static fn($s) => 'machineryElectricity' === $s->key))[0];

        $this->assertSame(Source::MachineryOperation, $machineryFuels->source);
        $this->assertSame(Source::MachineryOperation, $machineryElectricity->source);
    }

    public function testCelebrationAndBreakdownHaveNoSections(): void
    {
        $this->assertSame([], Phase::Celebration->sections());
        $this->assertSame([], Phase::Breakdown->sections());
    }

    public function testKeysAreUniquePerPhase(): void
    {
        foreach ([Phase::Materials, Phase::Assembly] as $phase) {
            $keys = array_map(static fn($s) => $s->key, $phase->sections());
            $this->assertSame($keys, array_unique($keys));
        }
    }
}
