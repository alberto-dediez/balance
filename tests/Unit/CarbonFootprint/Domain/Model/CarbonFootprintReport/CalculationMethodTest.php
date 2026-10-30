<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\DataQuality;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
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
