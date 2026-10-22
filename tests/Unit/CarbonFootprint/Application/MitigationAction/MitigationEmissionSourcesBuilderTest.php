<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MobilityActivityMother;
use PHPUnit\Framework\TestCase;

class MitigationEmissionSourcesBuilderTest extends TestCase
{
    use MitigationPlanContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initMitigationPlan();
    }

    public function testGroupsTheActivitiesBySourceAndItem(): void
    {
        $footprint = $this->propertyFootprint();

        $sources = $this->emissionSourcesBuilder()->build($footprint);

        $this->assertCount(2, $sources);
        $this->assertSame(Source::FixedFacilities, $sources[0]->source);
        $this->assertSame(Scope::Scope1, $sources[0]->scope);
        $this->assertSame((string) $this->naturalGasId, $sources[0]->itemId);
        $this->assertSame(1000.0, $sources[0]->emissions);
        $this->assertSame(Source::BuildingElectricity, $sources[1]->source);
        $this->assertSame((string) $this->marketerId, $sources[1]->itemId);
        $this->assertSame(500.0, $sources[1]->emissions);
    }

    public function testABusinessTravelActivityProducesAScope3Source(): void
    {
        $footprint = $this->propertyFootprint(withActivities: false);
        $activity = MobilityActivityMother::registered();
        $this->activities->save($activity);
        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope3, Source::BusinessTravel));

        $sources = $this->emissionSourcesBuilder()->build($footprint);

        $this->assertCount(1, $sources);
        $this->assertSame(Source::BusinessTravel, $sources[0]->source);
        $this->assertSame(Scope::Scope3, $sources[0]->scope);
    }

    public function testSumsThePropertiesOfACompany(): void
    {
        $footprint = $this->companyFootprint();

        $sources = $this->emissionSourcesBuilder()->build($footprint);

        $this->assertCount(1, $sources);
        $this->assertSame(1000.0, $sources[0]->emissions);
    }

    public function testElectricityWithoutMarketerUsesAnEmptyItem(): void
    {
        $footprint = $this->propertyFootprint(withActivities: false);
        $footprint->addActivity($this->guaranteeOfOriginElectricityActivity('20')->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $footprint->addActivity($this->guaranteeOfOriginElectricityActivity('30')->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        $sources = $this->emissionSourcesBuilder()->build($footprint);

        $this->assertCount(1, $sources);
        $this->assertSame('', $sources[0]->itemId);
        $this->assertSame(50.0, $sources[0]->emissions);
        $this->assertSame('electricity', $sources[0]->itemName);
    }

    public function testDiscardsSourcesWithoutEmissions(): void
    {
        $footprint = $this->propertyFootprint(withActivities: false);
        $footprint->addActivity($this->guaranteeOfOriginElectricityActivity('0')->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        $this->assertSame([], $this->emissionSourcesBuilder()->build($footprint));
    }

    public function testTheWellToTankProducesScope3SourcesLinkedToTheirOrigin(): void
    {
        $footprint = $this->propertyFootprintWithWellToTank();

        $sources = $this->emissionSourcesBuilder()->build($footprint);

        $this->assertCount(4, $sources);
        $this->assertFalse($sources[0]->isDerived());
        $this->assertFalse($sources[1]->isDerived());

        $this->assertSame(Scope::Scope3, $sources[2]->scope);
        $this->assertSame(Source::FuelAndEnergyRelated, $sources[2]->source);
        $this->assertSame((string) $this->naturalGasId, $sources[2]->itemId);
        $this->assertSame($sources[0]->itemName, $sources[2]->itemName);
        $this->assertSame(90.0, $sources[2]->emissions);
        $this->assertSame($sources[0]->key(), $sources[2]->originKey);
        $this->assertSame($sources[0]->key(), $sources[2]->reductionKey());

        $this->assertSame(Source::FuelAndEnergyRelated, $sources[3]->source);
        $this->assertSame(30.0, $sources[3]->emissions);
        $this->assertSame($sources[1]->key(), $sources[3]->originKey);
    }

    public function testTheWellToTankDoesNotProduceSourcesWhenTheScope3IsExcluded(): void
    {
        $footprint = $this->propertyFootprintWithWellToTank();
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $sources = $this->emissionSourcesBuilder()->build($footprint);

        $this->assertCount(2, $sources);
        $this->assertFalse($sources[0]->isDerived());
        $this->assertFalse($sources[1]->isDerived());
    }

    public function testTheWellToTankDoesNotProduceSourcesWhenTheSourceIsExcluded(): void
    {
        $footprint = $this->propertyFootprintWithWellToTank();
        $footprint->excludeSource(Scope::Scope3, Source::FuelAndEnergyRelated, ExclusionReason::NoDataAvailable);

        $sources = $this->emissionSourcesBuilder()->build($footprint);

        $this->assertCount(2, $sources);
        $this->assertFalse($sources[0]->isDerived());
        $this->assertFalse($sources[1]->isDerived());
    }

    private function propertyFootprintWithWellToTank(): PropertyCarbonFootprint
    {
        $footprint = $this->propertyFootprint(withActivities: false);
        $footprint->addActivity($this->naturalGasActivity('600', '60')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($this->naturalGasActivity('400', '30')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($this->marketerElectricityActivity('500', '30')->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        return $footprint;
    }
}
