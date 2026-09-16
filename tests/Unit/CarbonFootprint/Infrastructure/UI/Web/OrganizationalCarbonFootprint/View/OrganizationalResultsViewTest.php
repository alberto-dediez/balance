<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintExclusion;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintExclusionId;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\OrganizationalResultsView;
use App\Property\Domain\Model\Property\PropertyId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class OrganizationalResultsViewTest extends TestCase
{
    private OrganizationalResultsView $sut;

    protected function setUp(): void
    {
        $this->sut = new OrganizationalResultsView(new FakeTranslator());
    }

    public function testSourceChartSkipsSourcesWithoutEmissions(): void
    {
        $view = $this->sut->build($this->bySource(), $this->byScope(), $this->byScopeAndSource(), []);

        $this->assertSame([Source::FixedFacilities->value, Source::BuildingElectricity->value], $view['sourceChart']['labels']);
        $this->assertSame([180.0, 2500.0], $view['sourceChart']['values']);
    }

    public function testScopeChartAlwaysHasBothScopes(): void
    {
        $view = $this->sut->build($this->bySource(), $this->byScope(), $this->byScopeAndSource(), []);

        $this->assertSame([Scope::Scope1->value, Scope::Scope2->value], $view['scopeChart']['labels']);
        $this->assertSame([180.0, 2500.0], $view['scopeChart']['values']);
    }

    public function testScopeSourceSeriesOnlyIncludesSourcesWithEmissions(): void
    {
        $view = $this->sut->build($this->bySource(), $this->byScope(), $this->byScopeAndSource(), []);

        $this->assertCount(2, $view['scopeSourceSeries']);
        $this->assertSame(Source::FixedFacilities->value, $view['scopeSourceSeries'][0]['name']);
        $this->assertSame([180.0, 0.0], $view['scopeSourceSeries'][0]['data']);
    }

    public function testExclusionsAreTranslated(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
        $exclusion = new PropertyCarbonFootprintExclusion(
            id: PropertyCarbonFootprintExclusionId::generate(),
            propertyCarbonFootprint: $footprint,
            scope: Scope::Scope1,
            source: Source::RefrigerantLeaks,
            reason: ExclusionReason::NoDataAvailable,
            comment: 'Sin datos',
        );

        $view = $this->sut->build($this->bySource(), $this->byScope(), $this->byScopeAndSource(), [$exclusion]);

        $this->assertCount(1, $view['exclusions']);
        $this->assertSame(Scope::Scope1->value, $view['exclusions'][0]['scope']);
        $this->assertSame(Source::RefrigerantLeaks->value, $view['exclusions'][0]['source']);
        $this->assertSame('Sin datos', $view['exclusions'][0]['comment']);
    }

    public function testPropertyChartLabelsTheUnassignedBlock(): void
    {
        $property = PropertyMother::from(propertyId: new PropertyId('property-1'), name: 'Hotel A');

        $view = $this->sut->build(
            $this->bySource(),
            $this->byScope(),
            $this->byScopeAndSource(),
            [],
            ['' => new Emissions('1250', '1250', '0', '0'), 'property-1' => new Emissions('2680', '2680', '0', '0')],
            [$property],
        );

        $this->assertSame(['Sin asignar a propiedad', 'Hotel A'], $view['propertyChart']['labels']);
        $this->assertSame([1250.0, 2680.0], $view['propertyChart']['values']);
    }

    public function testPropertyChartIsEmptyWithoutData(): void
    {
        $view = $this->sut->build($this->bySource(), $this->byScope(), $this->byScopeAndSource(), []);

        $this->assertSame([], $view['propertyChart']['labels']);
    }

    /** @return array<string, Emissions> */
    private function bySource(): array
    {
        $bySource = [];
        foreach (Source::cases() as $source) {
            $bySource[$source->value] = new Emissions();
        }
        $bySource[Source::FixedFacilities->value] = new Emissions('180', '180', '0', '0');
        $bySource[Source::BuildingElectricity->value] = new Emissions('2500', '2500', '0', '0');

        return $bySource;
    }

    /** @return array<string, Emissions> */
    private function byScope(): array
    {
        return [
            Scope::Scope1->value => new Emissions('180', '180', '0', '0'),
            Scope::Scope2->value => new Emissions('2500', '2500', '0', '0'),
        ];
    }

    /** @return array<string, array<string, Emissions>> */
    private function byScopeAndSource(): array
    {
        $empty = [];
        foreach (Source::cases() as $source) {
            $empty[$source->value] = new Emissions();
        }

        $scope1 = $empty;
        $scope1[Source::FixedFacilities->value] = new Emissions('180', '180', '0', '0');

        $scope2 = $empty;
        $scope2[Source::BuildingElectricity->value] = new Emissions('2500', '2500', '0', '0');

        return [Scope::Scope1->value => $scope1, Scope::Scope2->value => $scope2];
    }
}
