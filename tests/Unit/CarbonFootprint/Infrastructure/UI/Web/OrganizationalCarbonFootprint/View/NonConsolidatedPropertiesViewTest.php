<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope\NonConsolidatedPropertyRow;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\NonConsolidatedPropertiesView;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use PHPUnit\Framework\TestCase;

class NonConsolidatedPropertiesViewTest extends TestCase
{
    public function testBothSourcesAreAlwaysPresent(): void
    {
        $this->assertSame([
            'downstream_leased_assets' => ['rows' => [], 'total' => '0'],
            'franchises' => ['rows' => [], 'total' => '0'],
        ], NonConsolidatedPropertiesView::build([]));
    }

    public function testGroupsTheRowsBySourceAndAddsUpTheAppliedEmissions(): void
    {
        $franchised = PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Franquicia', new CompanyId('company-1'), new PropertyId('franchised-1'), OperatingRegime::Franchised);

        $view = NonConsolidatedPropertiesView::build([
            new NonConsolidatedPropertyRow($franchised, $franchised->id(), Source::Franchises, PropertyCarbonFootprintId::generate(), 2025, new Emissions('5000'), null, new Emissions('5000')),
            new NonConsolidatedPropertyRow(null, new PropertyId('franchised-2'), Source::Franchises, null, 2025, new Emissions(), '200', new Emissions('200')),
            new NonConsolidatedPropertyRow(null, new PropertyId('leased-out-1'), Source::DownstreamLeasedAssets, null, 2025, new Emissions(), '4500', new Emissions('4500')),
        ]);

        $this->assertCount(2, $view['franchises']['rows']);
        $this->assertSame(5200.0, (float) $view['franchises']['total']);
        $this->assertSame(4500.0, (float) $view['downstream_leased_assets']['total']);

        $this->assertSame([
            'propertyId' => 'franchised-1',
            'propertyName' => 'Franquicia',
            'hasOrigin' => true,
            'year' => 2025,
            'calculated' => '5000',
            'manual' => null,
            'applied' => '5000',
        ], $view['franchises']['rows'][0]);
        $this->assertSame('franchised-2', $view['franchises']['rows'][1]['propertyName']);
        $this->assertFalse($view['franchises']['rows'][1]['hasOrigin']);
        $this->assertSame('200', $view['franchises']['rows'][1]['manual']);
    }
}
