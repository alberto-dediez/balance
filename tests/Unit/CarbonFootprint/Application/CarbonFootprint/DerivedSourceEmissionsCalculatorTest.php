<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\TransportActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use PHPUnit\Framework\TestCase;

final class DerivedSourceEmissionsCalculatorTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
    }

    public function testAddsUpTheWellToTankEmissionsOfScope1And2CombustiblesAndElectricity(): void
    {
        $footprint = $this->footprintWithScope1And2Activities();

        $emissions = $this->createSUT()->calculate($footprint, Scope::Scope3);

        $this->assertSame([Source::FuelAndEnergyRelated->value, Source::DownstreamLeasedAssets->value, Source::Franchises->value], array_keys($emissions));
        $this->assertSame('244', $emissions[Source::FuelAndEnergyRelated->value]->co2eq());
    }

    public function testStagesWithoutDerivedSourcesReturnNothing(): void
    {
        $footprint = $this->footprintWithScope1And2Activities();

        $this->assertSame([], $this->createSUT()->calculate($footprint, Scope::Scope1));
        $this->assertSame([], $this->createSUT()->calculate($footprint, Phase::Assembly));
    }

    public function testIsZeroWhenTheWholeScopeIsExcluded(): void
    {
        $footprint = $this->footprintWithScope1And2Activities();
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $emissions = $this->createSUT()->calculate($footprint, Scope::Scope3);

        $this->assertSame('0', $emissions[Source::FuelAndEnergyRelated->value]->co2eq());
        $this->assertSame([], $this->createSUT()->lines($footprint, Scope::Scope3));
    }

    public function testIsZeroWhenTheSourceIsExcluded(): void
    {
        $footprint = $this->footprintWithScope1And2Activities();
        $footprint->excludeSource(Scope::Scope3, Source::FuelAndEnergyRelated, ExclusionReason::NotSignificant);

        $emissions = $this->createSUT()->calculate($footprint, Scope::Scope3);

        $this->assertSame('0', $emissions[Source::FuelAndEnergyRelated->value]->co2eq());
    }

    public function testLinesHaveOneEntryPerActivityWithWellToTankEmissions(): void
    {
        $footprint = $this->footprintWithScope1And2Activities();
        $withoutWellToTank = CombustibleActivityMother::registered();
        $this->activityRepository->save($withoutWellToTank);
        $footprint->addActivity($withoutWellToTank->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $lines = $this->createSUT()->lines($footprint, Scope::Scope3);

        $this->assertCount(2, $lines);
        $this->assertSame([ActivityType::Combustible, ActivityType::Electricity], array_map(static fn($line) => $line->activityType, $lines));
        $this->assertSame([Scope::Scope1, Scope::Scope2], array_map(static fn($line) => $line->origin->stage, $lines));
        $this->assertSame(Source::FuelAndEnergyRelated, $lines[0]->source);
        $this->assertSame('122.0', $lines[0]->emissions->co2eq());
    }

    public function testCalculateAddsUpTheNonConsolidatedPropertiesOfACompanyFootprint(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('franchised-1'), Source::Franchises, new Emissions('5000'));
        $footprint->recordNonConsolidatedProperty(new PropertyId('franchised-2'), Source::Franchises, null, new Emissions('100'), '200');
        $footprint->recordNonConsolidatedProperty(new PropertyId('leased-out-1'), Source::DownstreamLeasedAssets, null, new Emissions(), '4500');

        $emissions = $this->createSUT()->calculate($footprint, Scope::Scope3);

        $this->assertSame(5200.0, (float) $emissions[Source::Franchises->value]->co2eq());
        $this->assertSame(4500.0, (float) $emissions[Source::DownstreamLeasedAssets->value]->co2eq());
        $this->assertSame('0', $emissions[Source::FuelAndEnergyRelated->value]->co2eq());
    }

    public function testNonConsolidatedPropertyLinesOnlyForCompanyFootprintsInScope3(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('franchised-1'), Source::Franchises, new Emissions('5000'));

        $this->assertCount(1, $this->createSUT()->nonConsolidatedPropertyLines($footprint, Scope::Scope3));
        $this->assertSame([], $this->createSUT()->nonConsolidatedPropertyLines($footprint, Scope::Scope1));
        $this->assertSame([], $this->createSUT()->nonConsolidatedPropertyLines($this->footprintWithScope1And2Activities(), Scope::Scope3));
    }

    public function testNonConsolidatedPropertyLinesAreEmptyWhenTheSourceIsExcluded(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('franchised-1'), Source::Franchises, new Emissions('5000'));
        $footprint->excludeSource(Scope::Scope3, Source::Franchises, ExclusionReason::NotApplicable);

        $this->assertSame([], $this->createSUT()->nonConsolidatedPropertyLines($footprint, Scope::Scope3));
    }

    public function testNonConsolidatedPropertyLinesAreEmptyWhenTheScopeIsExcluded(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('franchised-1'), Source::Franchises, new Emissions('5000'));
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->assertSame([], $this->createSUT()->nonConsolidatedPropertyLines($footprint, Scope::Scope3));
    }

    private function footprintWithScope1And2Activities(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $combustible = CombustibleActivityMother::registeredWithWellToTank();
        $electricity = ElectricityActivityMother::registeredWithWellToTank();
        $vehicle = TransportActivityMother::registered();
        $refrigerantGas = RefrigerantGasActivityMother::registered();

        foreach ([$combustible, $electricity, $vehicle, $refrigerantGas] as $activity) {
            $this->activityRepository->save($activity);
        }

        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($vehicle->id(), new ActivityPlacement(Scope::Scope1, Source::OwnVehicles));
        $footprint->addActivity($refrigerantGas->id(), new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks));
        $footprint->addActivity($electricity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        return $footprint;
    }

    private function createSUT(): DerivedSourceEmissionsCalculator
    {
        return new DerivedSourceEmissionsCalculator($this->activityRepository);
    }
}
