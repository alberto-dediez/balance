<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

class OperatorContributionEmissionsCalculatorTest extends OperatorContributionServiceTestCase
{
    public function testSumsTheEmissionsByScope(): void
    {
        $contribution = $this->contribution($this->companyFootprint());
        $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, 1000.0);
        $this->addActivity($contribution, Scope::Scope1, Source::OwnVehicles, 500.0);
        $this->addActivity($contribution, Scope::Scope2, Source::BuildingElectricity, 3000.0);

        $byScope = $this->emissionsCalculator->byScope($contribution);

        $this->assertSame(1500.0, (float) $byScope['scope_1']->co2eq());
        $this->assertSame(3000.0, (float) $byScope['scope_2']->co2eq());
        $this->assertSame(4500.0, (float) $this->emissionsCalculator->scope1And2($contribution)->co2eq());

        $summary = $this->emissionsCalculator->summary($contribution);
        $this->assertSame($contribution, $summary->contribution);
        $this->assertSame(4500.0, (float) $summary->total->co2eq());
    }

    public function testWithoutActivitiesIsZero(): void
    {
        $contribution = $this->contribution($this->companyFootprint());

        $this->assertSame(0.0, (float) $this->emissionsCalculator->scope1And2($contribution)->co2eq());
    }
}
