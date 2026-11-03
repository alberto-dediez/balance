<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use PHPUnit\Framework\TestCase;

final class SourceTest extends TestCase
{
    public function testTheScope3SourcesHaveTheirGhgProtocolCategory(): void
    {
        $this->assertSame(1, Source::PurchasedGoodsAndServices->ghgProtocolCategory());
        $this->assertSame(2, Source::CapitalGoods->ghgProtocolCategory());
        $this->assertSame(3, Source::FuelAndEnergyRelated->ghgProtocolCategory());
        $this->assertSame(4, Source::UpstreamTransport->ghgProtocolCategory());
        $this->assertSame(5, Source::OperationsWaste->ghgProtocolCategory());
        $this->assertSame(6, Source::BusinessTravel->ghgProtocolCategory());
        $this->assertSame(7, Source::EmployeeCommuting->ghgProtocolCategory());
        $this->assertSame(9, Source::DownstreamTransport->ghgProtocolCategory());
        $this->assertSame(10, Source::SoldProductsProcessing->ghgProtocolCategory());
        $this->assertSame(11, Source::SoldProductsUse->ghgProtocolCategory());
        $this->assertSame(12, Source::SoldProductsEndOfLife->ghgProtocolCategory());
        $this->assertSame(13, Source::DownstreamLeasedAssets->ghgProtocolCategory());
        $this->assertSame(14, Source::Franchises->ghgProtocolCategory());
        $this->assertSame(15, Source::Investments->ghgProtocolCategory());
    }

    public function testTheOtherSourcesHaveNoGhgProtocolCategory(): void
    {
        foreach ([Source::Materials, Source::FixedFacilities, Source::FreightTransport, Source::MachineryOperation, Source::BuildingElectricity, Source::SuppliersAndStaffAccommodation, Source::AttendeesAccommodation, Source::SuppliersAndStaffMobility, Source::AttendeesMobility, Source::WasteManagement, Source::OwnVehicles, Source::RefrigerantLeaks] as $source) {
            $this->assertNull($source->ghgProtocolCategory(), $source->value);
        }
    }

    public function testOfGhgProtocolCategoryIsTheInverseOfGhgProtocolCategory(): void
    {
        foreach (Source::cases() as $source) {
            $category = $source->ghgProtocolCategory();

            if (null === $category) {
                continue;
            }

            $this->assertSame($source, Source::ofGhgProtocolCategory($category));
        }
    }

    public function testOfGhgProtocolCategoryReturnsNullWithoutSource(): void
    {
        $this->assertNull(Source::ofGhgProtocolCategory(8));
        $this->assertNull(Source::ofGhgProtocolCategory(16));
    }
}
