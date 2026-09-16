<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use PHPUnit\Framework\TestCase;

final class ActivityPlacementTest extends TestCase
{
    public function testEqualsWhenEveryFieldMatches(): void
    {
        $placement = new ActivityPlacement(Phase::Assembly, Source::FixedFacilities);

        $this->assertTrue($placement->equals(new ActivityPlacement(Phase::Assembly, Source::FixedFacilities)));
    }

    public function testDiffersByStage(): void
    {
        $placement = new ActivityPlacement(Phase::Assembly, Source::FixedFacilities);

        $this->assertFalse($placement->equals(new ActivityPlacement(Phase::Celebration, Source::FixedFacilities)));
    }

    public function testDiffersBySource(): void
    {
        $placement = new ActivityPlacement(Phase::Assembly, Source::FixedFacilities);

        $this->assertFalse($placement->equals(new ActivityPlacement(Phase::Assembly, Source::WasteManagement)));
    }

    public function testDiffersByProviderId(): void
    {
        $placement = new ActivityPlacement(Phase::Assembly, Source::FixedFacilities, new ProviderId('provider-1'));

        $this->assertFalse($placement->equals(new ActivityPlacement(Phase::Assembly, Source::FixedFacilities)));
        $this->assertFalse($placement->equals(new ActivityPlacement(Phase::Assembly, Source::FixedFacilities, new ProviderId('provider-2'))));
        $this->assertTrue($placement->equals(new ActivityPlacement(Phase::Assembly, Source::FixedFacilities, new ProviderId('provider-1'))));
    }

    public function testDiffersByFromEmployees(): void
    {
        $placement = new ActivityPlacement(Phase::Assembly, Source::FixedFacilities, null, true);

        $this->assertFalse($placement->equals(new ActivityPlacement(Phase::Assembly, Source::FixedFacilities)));
        $this->assertTrue($placement->equals(new ActivityPlacement(Phase::Assembly, Source::FixedFacilities, null, true)));
    }

    public function testDiffersByPropertyId(): void
    {
        $placement = new ActivityPlacement(Phase::Assembly, Source::FixedFacilities, propertyId: new PropertyId('property-1'));

        $this->assertFalse($placement->equals(new ActivityPlacement(Phase::Assembly, Source::FixedFacilities)));
        $this->assertFalse($placement->equals(new ActivityPlacement(Phase::Assembly, Source::FixedFacilities, propertyId: new PropertyId('property-2'))));
        $this->assertTrue($placement->equals(new ActivityPlacement(Phase::Assembly, Source::FixedFacilities, propertyId: new PropertyId('property-1'))));
    }

    public function testWithSourceKeepsTheRestOfTheFields(): void
    {
        $placement = new ActivityPlacement(Phase::Assembly, Source::FixedFacilities, new ProviderId('provider-1'), true, new PropertyId('property-1'));

        $other = $placement->withSource(Source::WasteManagement);

        $this->assertSame(Source::WasteManagement, $other->source);
        $this->assertSame(Phase::Assembly, $other->stage);
        $this->assertTrue($other->fromEmployees);
        $this->assertNotNull($other->providerId);
        $this->assertSame('provider-1', $other->providerId->id());
        $this->assertNotNull($other->propertyId);
        $this->assertSame('property-1', $other->propertyId->id());
    }
}
