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

    public function testCelebrationSections(): void
    {
        $sections = Phase::Celebration->sections();

        $this->assertCount(8, $sections);
        $this->assertSame(
            ['combustibles', 'roadTransports', 'machineryFuels', 'machineryElectricity', 'buildingElectricity', 'overnightStays', 'mobilities', 'wastes'],
            array_map(static fn($s) => $s->key, $sections),
        );
    }

    public function testBreakdownSections(): void
    {
        $sections = Phase::Breakdown->sections();

        $this->assertCount(8, $sections);
        $this->assertSame(
            ['combustibles', 'roadTransports', 'machineryFuels', 'machineryElectricity', 'buildingElectricity', 'overnightStays', 'mobilities', 'wastes'],
            array_map(static fn($s) => $s->key, $sections),
        );
    }

    public function testKeysAreUniquePerPhase(): void
    {
        foreach ([Phase::Materials, Phase::Assembly, Phase::Celebration, Phase::Breakdown] as $phase) {
            $keys = array_map(static fn($s) => $s->key, $phase->sections());
            $this->assertSame($keys, array_unique($keys));
        }
    }

    public function testMaterialsHasNoProviderSections(): void
    {
        $this->assertSame([], Phase::Materials->providerSections());
    }

    public function testAssemblyProviderSectionsIsASubsetOfSections(): void
    {
        $sections = Phase::Assembly->providerSections();

        $this->assertSame(
            ['combustibles', 'machineryFuels', 'overnightStays', 'mobilities'],
            array_map(static fn($s) => $s->key, $sections),
        );
    }

    public function testCelebrationAndBreakdownProviderSectionsMatchAssembly(): void
    {
        $this->assertEquals(Phase::Assembly->providerSections(), Phase::Celebration->providerSections());
        $this->assertEquals(Phase::Assembly->providerSections(), Phase::Breakdown->providerSections());
    }

    public function testProviderSectionsIsAStrictSubsetOfSections(): void
    {
        foreach ([Phase::Assembly, Phase::Celebration, Phase::Breakdown] as $phase) {
            $sectionKeys = array_map(static fn($s) => $s->key, $phase->sections());
            $providerSectionKeys = array_map(static fn($s) => $s->key, $phase->providerSections());

            foreach ($providerSectionKeys as $key) {
                $this->assertContains($key, $sectionKeys);
            }

            $this->assertNotSame($sectionKeys, $providerSectionKeys);
            $this->assertNotContains('roadTransports', $providerSectionKeys);
            $this->assertNotContains('machineryElectricity', $providerSectionKeys);
            $this->assertNotContains('buildingElectricity', $providerSectionKeys);
            $this->assertNotContains('wastes', $providerSectionKeys);
        }
    }
}
