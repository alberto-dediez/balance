<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Event\DomainEventPublisher;
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
}
