<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\DataQuality;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use PHPUnit\Framework\TestCase;

final class CalculationMethodTest extends TestCase
{
    public function testEveryMethodHasItsDataQuality(): void
    {
        $this->assertSame(DataQuality::High, CalculationMethod::SupplierSpecific->dataQuality());
        $this->assertSame(DataQuality::Medium, CalculationMethod::ReportedByOrganization->dataQuality());
        $this->assertSame(DataQuality::Medium, CalculationMethod::ActivityBased->dataQuality());
        $this->assertSame(DataQuality::Low, CalculationMethod::SpendBased->dataQuality());
        $this->assertSame(DataQuality::Medium, CalculationMethod::DistanceBased->dataQuality());
        $this->assertSame(DataQuality::Medium, CalculationMethod::FuelBased->dataQuality());
        $this->assertSame(DataQuality::Medium, CalculationMethod::WasteTypeSpecific->dataQuality());
        $this->assertSame(DataQuality::Medium, CalculationMethod::AverageData->dataQuality());
        $this->assertSame(DataQuality::Medium, CalculationMethod::EmployeeSurveyExtrapolation->dataQuality());
        $this->assertSame(DataQuality::Medium, CalculationMethod::DerivedWellToTank->dataQuality());
        $this->assertSame(DataQuality::High, CalculationMethod::OperatorFootprint->dataQuality());
        $this->assertSame(DataQuality::Medium, CalculationMethod::OperatorContribution->dataQuality());
        $this->assertSame(DataQuality::Low, CalculationMethod::ManualValue->dataQuality());
    }

    public function testEveryMethodHasALabel(): void
    {
        foreach (CalculationMethod::cases() as $method) {
            $this->assertNotSame('', $method->label());
        }
    }

    public function testTheReportedEmissionsOfAProviderAreSupplierSpecific(): void
    {
        $placement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: ProviderId::generate());

        $this->assertSame(CalculationMethod::SupplierSpecific, CalculationMethod::forActivity(ActivityType::ReportedEmissions, Unit::Kilogram, $placement));
    }

    public function testTheReportedEmissionsWithoutProviderAreReportedByTheOrganization(): void
    {
        $placement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport);

        $this->assertSame(CalculationMethod::ReportedByOrganization, CalculationMethod::forActivity(ActivityType::ReportedEmissions, Unit::Kilogram, $placement));
    }

    public function testACurrencyAmountIsSpendBasedEvenInTransport(): void
    {
        $placement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport);

        $this->assertSame(CalculationMethod::SpendBased, CalculationMethod::forActivity(ActivityType::Transport, Unit::Euro, $placement));
    }

    public function testAnEmployeeActivityIsAnExtrapolationOfTheSurveyEvenInKilometres(): void
    {
        $placement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true);

        $this->assertSame(CalculationMethod::EmployeeSurveyExtrapolation, CalculationMethod::forActivity(ActivityType::Mobility, Unit::Kilometer, $placement));
    }

    public function testTheScopeOneAndTwoActivitiesAreActivityBased(): void
    {
        $this->assertSame(CalculationMethod::ActivityBased, CalculationMethod::forActivity(ActivityType::Combustible, Unit::Kilogram, new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)));
        $this->assertSame(CalculationMethod::ActivityBased, CalculationMethod::forActivity(ActivityType::Electricity, Unit::KWh, new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity)));
    }

    public function testTheFreightInTonneKilometresAndTheMobilityInKilometresAreDistanceBased(): void
    {
        $this->assertSame(CalculationMethod::DistanceBased, CalculationMethod::forActivity(ActivityType::Transport, Unit::TonneKilometre, new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport)));
        $this->assertSame(CalculationMethod::DistanceBased, CalculationMethod::forActivity(ActivityType::Mobility, Unit::Kilometer, new ActivityPlacement(Scope::Scope3, Source::BusinessTravel)));
    }

    public function testTheTransportOrMobilityInLitresKilowattHoursOrKilogramsIsFuelBased(): void
    {
        $placement = new ActivityPlacement(Scope::Scope3, Source::DownstreamTransport);

        foreach ([Unit::Liter, Unit::KWh, Unit::Kilogram] as $unit) {
            $this->assertSame(CalculationMethod::FuelBased, CalculationMethod::forActivity(ActivityType::Transport, $unit, $placement));
        }

        $mobilityPlacement = new ActivityPlacement(Scope::Scope3, Source::BusinessTravel);

        foreach ([Unit::Liter, Unit::Kilogram] as $unit) {
            $this->assertSame(CalculationMethod::FuelBased, CalculationMethod::forActivity(ActivityType::Mobility, $unit, $mobilityPlacement));
        }
    }

    public function testATransportOrMobilityInPiecesIsAverageData(): void
    {
        $this->assertSame(CalculationMethod::AverageData, CalculationMethod::forActivity(ActivityType::Transport, Unit::Piece, new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport)));
    }

    public function testTheWasteIsWasteTypeSpecific(): void
    {
        $this->assertSame(CalculationMethod::WasteTypeSpecific, CalculationMethod::forActivity(ActivityType::Waste, Unit::Kilogram, new ActivityPlacement(Scope::Scope3, Source::OperationsWaste)));
        $this->assertSame(CalculationMethod::WasteTypeSpecific, CalculationMethod::forActivity(ActivityType::Waste, Unit::Kilogram, new ActivityPlacement(Scope::Scope3, Source::SoldProductsEndOfLife)));
    }

    public function testThePhysicalPurchasesStaysProcessesUseProfilesAndSectorsAreAverageData(): void
    {
        $this->assertSame(CalculationMethod::AverageData, CalculationMethod::forActivity(ActivityType::PurchaseService, Unit::Kilogram, new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices)));
        $this->assertSame(CalculationMethod::AverageData, CalculationMethod::forActivity(ActivityType::OvernightStay, Unit::Pernoctacion, new ActivityPlacement(Scope::Scope3, Source::BusinessTravel)));
        $this->assertSame(CalculationMethod::AverageData, CalculationMethod::forActivity(ActivityType::Process, Unit::Piece, new ActivityPlacement(Scope::Scope3, Source::SoldProductsProcessing)));
        $this->assertSame(CalculationMethod::AverageData, CalculationMethod::forActivity(ActivityType::UseProfile, Unit::Piece, new ActivityPlacement(Scope::Scope3, Source::SoldProductsUse)));
        $this->assertSame(CalculationMethod::AverageData, CalculationMethod::forActivity(ActivityType::EconomicSector, Unit::Piece, new ActivityPlacement(Scope::Scope3, Source::Investments)));
        $this->assertSame(CalculationMethod::AverageData, CalculationMethod::forActivity(ActivityType::CapitalGood, Unit::Piece, new ActivityPlacement(Scope::Scope3, Source::CapitalGoods)));
    }

    public function testAManualValuePrevailsOverTheContributionAndTheFootprintOfANonConsolidatedProperty(): void
    {
        $line = $this->nonConsolidatedPropertyLine(PropertyCarbonFootprintId::generate(), OperatorContributionId::generate(), '4500');

        $this->assertSame(CalculationMethod::ManualValue, CalculationMethod::forNonConsolidatedPropertyLine($line));
    }

    public function testAnOperatorContributionPrevailsOverTheFootprintOfANonConsolidatedProperty(): void
    {
        $line = $this->nonConsolidatedPropertyLine(PropertyCarbonFootprintId::generate(), OperatorContributionId::generate(), null);

        $this->assertSame(CalculationMethod::OperatorContribution, CalculationMethod::forNonConsolidatedPropertyLine($line));
    }

    public function testTheFootprintOfANonConsolidatedPropertyIsTheOperatorFootprintMethod(): void
    {
        $line = $this->nonConsolidatedPropertyLine(PropertyCarbonFootprintId::generate(), null, null);

        $this->assertSame(CalculationMethod::OperatorFootprint, CalculationMethod::forNonConsolidatedPropertyLine($line));
    }

    public function testANonConsolidatedPropertyWithoutOriginHasNoMethod(): void
    {
        $this->assertNull(CalculationMethod::forNonConsolidatedPropertyLine($this->nonConsolidatedPropertyLine(null, null, null)));
    }

    private function nonConsolidatedPropertyLine(?PropertyCarbonFootprintId $propertyCarbonFootprintId, ?OperatorContributionId $operatorContributionId, ?string $manualCo2eq): NonConsolidatedPropertyLine
    {
        return new NonConsolidatedPropertyLine(Source::Franchises, new PropertyId('property-franquicia'), $propertyCarbonFootprintId, $operatorContributionId, new Emissions('5000'), $manualCo2eq, new Emissions('5000'));
    }
}
