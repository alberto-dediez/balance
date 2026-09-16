<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use PHPUnit\Framework\TestCase;

final class ScopeTest extends TestCase
{
    public function testScope1HasThreeSections(): void
    {
        $sections = Scope::Scope1->sections();

        $this->assertCount(3, $sections);
        $this->assertSame(['combustibles', 'vehicles', 'refrigerantGases'], array_map(static fn($s) => $s->key, $sections));
        $this->assertSame([Source::FixedFacilities, Source::OwnVehicles, Source::RefrigerantLeaks], array_map(static fn($s) => $s->source, $sections));
        $this->assertSame([ActivityType::Combustible, ActivityType::RoadTransport, ActivityType::RefrigerantGas], array_map(static fn($s) => $s->activityType, $sections));
    }

    public function testScope2HasOnlyElectricity(): void
    {
        $sections = Scope::Scope2->sections();

        $this->assertCount(1, $sections);
        $this->assertSame('electricity', $sections[0]->key);
        $this->assertSame(Source::BuildingElectricity, $sections[0]->source);
        $this->assertSame(ActivityType::Electricity, $sections[0]->activityType);
    }

    public function testKeyIsTheValue(): void
    {
        $this->assertSame('scope_1', Scope::Scope1->key());
        $this->assertSame('scope_2', Scope::Scope2->key());
    }

    public function testStepNumber(): void
    {
        $this->assertSame(1, Scope::Scope1->stepNumber());
        $this->assertSame(2, Scope::Scope2->stepNumber());
    }

    public function testAnsweredStatus(): void
    {
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope1Answered, Scope::Scope1->answeredStatus());
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope2Answered, Scope::Scope2->answeredStatus());
    }
}
