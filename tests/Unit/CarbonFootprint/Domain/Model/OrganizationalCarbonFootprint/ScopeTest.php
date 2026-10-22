<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\OperatingRegime;
use PHPUnit\Framework\TestCase;

final class ScopeTest extends TestCase
{
    public function testScope1HasThreeSections(): void
    {
        $sections = Scope::Scope1->sections();

        $this->assertCount(3, $sections);
        $this->assertSame(['combustibles', 'vehicles', 'refrigerantGases'], array_map(static fn($s) => $s->key, $sections));
        $this->assertSame([Source::FixedFacilities, Source::OwnVehicles, Source::RefrigerantLeaks], array_map(static fn($s) => $s->source, $sections));
        $this->assertSame([ActivityType::Combustible, ActivityType::Transport, ActivityType::RefrigerantGas], array_map(static fn($s) => $s->activityType, $sections));
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
        $this->assertSame('scope_3', Scope::Scope3->key());
    }

    public function testStepNumber(): void
    {
        $this->assertSame(1, Scope::Scope1->stepNumber());
        $this->assertSame(2, Scope::Scope2->stepNumber());
        $this->assertSame(3, Scope::Scope3->stepNumber());
    }

    public function testAnsweredStatus(): void
    {
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope1Answered, Scope::Scope1->answeredStatus());
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope2Answered, Scope::Scope2->answeredStatus());
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope3Answered, Scope::Scope3->answeredStatus());
    }

    public function testOfSource(): void
    {
        $this->assertSame(Scope::Scope1, Scope::ofSource(Source::FixedFacilities));
        $this->assertSame(Scope::Scope1, Scope::ofSource(Source::OwnVehicles));
        $this->assertSame(Scope::Scope1, Scope::ofSource(Source::RefrigerantLeaks));
        $this->assertSame(Scope::Scope2, Scope::ofSource(Source::BuildingElectricity));
        $this->assertNull(Scope::ofSource(Source::Materials));
    }

    public function testScope3HasTwelveSectionsInOrder(): void
    {
        $sections = Scope::Scope3->sections();

        $this->assertCount(12, $sections);
        $this->assertSame([
            'purchasedGoodsAndServices',
            'capitalGoods',
            'upstreamTransport',
            'operationsWaste',
            'businessTravel',
            'businessTravelStays',
            'employeeCommuting',
            'downstreamTransport',
            'soldProductsProcessing',
            'soldProductsUse',
            'soldProductsEndOfLife',
            'investments',
        ], array_map(static fn($s) => $s->key, $sections));
        $this->assertSame([
            ActivityType::PurchaseService,
            ActivityType::CapitalGood,
            ActivityType::Transport,
            ActivityType::Waste,
            ActivityType::Mobility,
            ActivityType::OvernightStay,
            ActivityType::Mobility,
            ActivityType::Transport,
            ActivityType::Process,
            ActivityType::UseProfile,
            ActivityType::Waste,
            ActivityType::EconomicSector,
        ], array_map(static fn($s) => $s->activityType, $sections));
        $this->assertCount(2, array_filter($sections, static fn($s) => Source::BusinessTravel === $s->source));
    }

    public function testOfSourceResolvesEveryScope3Source(): void
    {
        foreach ([
            Source::PurchasedGoodsAndServices,
            Source::CapitalGoods,
            Source::FuelAndEnergyRelated,
            Source::UpstreamTransport,
            Source::OperationsWaste,
            Source::BusinessTravel,
            Source::EmployeeCommuting,
            Source::DownstreamTransport,
            Source::SoldProductsProcessing,
            Source::SoldProductsUse,
            Source::SoldProductsEndOfLife,
            Source::DownstreamLeasedAssets,
            Source::Franchises,
            Source::Investments,
        ] as $source) {
            $this->assertSame(Scope::Scope3, Scope::ofSource($source), $source->value);
        }
    }

    public function testOnlyScope3HasDerivedSources(): void
    {
        $this->assertSame([], Scope::Scope1->derivedSources());
        $this->assertSame([], Scope::Scope2->derivedSources());
        $this->assertSame([Source::FuelAndEnergyRelated, Source::DownstreamLeasedAssets, Source::Franchises], Scope::Scope3->derivedSources());
        $this->assertCount(14, Scope::Scope3->allSources());
        $this->assertContains(Source::FuelAndEnergyRelated, Scope::Scope3->allSources());
    }

    public function testOnlyScope3IsExcludable(): void
    {
        $this->assertFalse(Scope::Scope1->isExcludable());
        $this->assertFalse(Scope::Scope2->isExcludable());
        $this->assertTrue(Scope::Scope3->isExcludable());
    }

    public function testUpstreamAndDownstreamSources(): void
    {
        $this->assertSame([], Scope::Scope1->upstreamSources());
        $this->assertSame([], Scope::Scope2->downstreamSources());
        $this->assertSame([
            Source::PurchasedGoodsAndServices,
            Source::CapitalGoods,
            Source::FuelAndEnergyRelated,
            Source::UpstreamTransport,
            Source::OperationsWaste,
            Source::BusinessTravel,
            Source::EmployeeCommuting,
        ], Scope::Scope3->upstreamSources());
        $this->assertSame([
            Source::DownstreamTransport,
            Source::SoldProductsProcessing,
            Source::SoldProductsUse,
            Source::SoldProductsEndOfLife,
            Source::DownstreamLeasedAssets,
            Source::Franchises,
            Source::Investments,
        ], Scope::Scope3->downstreamSources());
    }

    public function testCompanyOnlySources(): void
    {
        $this->assertSame([], Scope::Scope1->companyOnlySources());
        $this->assertSame([], Scope::Scope2->companyOnlySources());
        $this->assertSame([Source::DownstreamLeasedAssets, Source::Franchises], Scope::Scope3->companyOnlySources());
    }

    public function testSourcesForSubjectType(): void
    {
        $propertySources = Scope::Scope3->sourcesFor(CarbonFootprintReportSubjectType::Property);
        $companySources = Scope::Scope3->sourcesFor(CarbonFootprintReportSubjectType::Company);

        $this->assertNotContains(Source::DownstreamLeasedAssets, $propertySources);
        $this->assertNotContains(Source::Franchises, $propertySources);
        $this->assertContains(Source::FuelAndEnergyRelated, $propertySources);
        $this->assertCount(12, $propertySources);
        $this->assertSame(Scope::Scope3->allSources(), $companySources);
        $this->assertSame(Scope::Scope1->allSources(), Scope::Scope1->sourcesFor(CarbonFootprintReportSubjectType::Property));
    }

    public function testSourceForOperatingRegime(): void
    {
        $this->assertNull(Scope::sourceForOperatingRegime(OperatingRegime::Owned));
        $this->assertNull(Scope::sourceForOperatingRegime(OperatingRegime::Leased));
        $this->assertNull(Scope::sourceForOperatingRegime(OperatingRegime::Managed));
        $this->assertSame(Source::Franchises, Scope::sourceForOperatingRegime(OperatingRegime::Franchised));
        $this->assertSame(Source::DownstreamLeasedAssets, Scope::sourceForOperatingRegime(OperatingRegime::LeasedOut));
    }

    public function testNonConsolidatedSourcesBelongToScope3(): void
    {
        $this->assertSame(Scope::Scope3, Scope::ofSource(Source::DownstreamLeasedAssets));
        $this->assertSame(Scope::Scope3, Scope::ofSource(Source::Franchises));
    }
}
