<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use PHPUnit\Framework\TestCase;

final class GasBreakdownCalculatorTest extends TestCase
{
    use KpiContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initKpiContext();
    }

    public function testSplitsByGasRefrigerantsAndRemainder(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
        $footprint->addActivity($this->combustibleActivityWithGases('1000', '900', '2000', '100')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($this->refrigerantActivity('2088')->id(), new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks));
        $footprint->addActivity($this->combustibleActivity('180')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $gas = $this->gasBreakdownCalculator()->calculate(
            $footprint,
            new Gwp(gwpCo2: 1.0, gwpCh4: 27.9, gwpN2O: 273.0),
            new Emissions('3400'),
        );

        $this->assertEqualsWithDelta(900.0 + 180.0, $gas->co2, 0.0001);
        $this->assertEqualsWithDelta(2.0 * 27.9, $gas->ch4, 0.0001);
        $this->assertEqualsWithDelta(0.1 * 273.0, $gas->n2o, 0.0001);
        $this->assertEqualsWithDelta(2088.0, $gas->refrigerants, 0.0001);
        $this->assertEqualsWithDelta(3400.0 - 1080.0 - 55.8 - 27.3 - 2088.0, $gas->remainder, 0.0001);
        $this->assertEqualsWithDelta($gas->total, $gas->co2 + $gas->ch4 + $gas->n2o + $gas->refrigerants + $gas->remainder, 0.01);
    }
}
