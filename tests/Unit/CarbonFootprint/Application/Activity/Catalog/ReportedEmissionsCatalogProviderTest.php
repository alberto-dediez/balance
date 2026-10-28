<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Application\Activity\Catalog\ReportedEmissionsCatalogProvider;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class ReportedEmissionsCatalogProviderTest extends TestCase
{
    public function testCatalogsAreEmpty(): void
    {
        $sut = new ReportedEmissionsCatalogProvider();

        $this->assertSame(ActivityType::ReportedEmissions, $sut->activityType());
        $this->assertSame([], $sut->catalog(2024, EmissionFactorGroupMother::defaultId()));
        $this->assertSame([], $sut->secondaryCatalog(2024, EmissionFactorGroupMother::defaultId()));
    }
}
