<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjector;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\MitigationPlanView;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSourceMother;
use PHPUnit\Framework\TestCase;

class MitigationPlanViewTest extends TestCase
{
    public function testBuildsTheActionRowsTheProjectionAndTheChart(): void
    {
        $sources = [MitigationEmissionSourceMother::naturalGas(1000.0), MitigationEmissionSourceMother::electricity(500.0)];
        $action = MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2);
        $action->setEffect(Source::RefrigerantLeaks, 'r410', 'R410A', 0.1);
        $actions = [$action];

        $view = (new MitigationPlanView())->build($actions, $sources, (new MitigationProjector())->project(2025, $sources, $actions));

        $this->assertCount(1, $view['actions']);
        $this->assertEqualsWithDelta(200.0, $view['actions'][0]['expectedReduction'], 0.001);
        $this->assertSame('Gas natural', $view['actions'][0]['effects'][0]['itemName']);
        $this->assertFalse($view['actions'][0]['effects'][0]['withoutEmissions']);
        $this->assertTrue($view['actions'][0]['effects'][1]['withoutEmissions']);
        $this->assertSame('R410A', $view['actions'][0]['effects'][1]['itemName']);

        $this->assertNotNull($view['projection']);
        $this->assertSame(2025, $view['projection']['baseYear']);
        $this->assertSame([2026, 2027, 2028, 2029, 2030], $view['projection']['years']);
        $this->assertCount(2, $view['projection']['rows']);
        $this->assertCount(6, $view['projection']['rows'][0]['values']);
        $this->assertEqualsWithDelta(1300.0, $view['projection']['totals'][5], 0.001);
        $this->assertNull($view['projection']['interannual'][0]);

        $this->assertNotNull($view['chart']);
        $this->assertSame(['2025', '2026', '2027', '2028', '2029', '2030'], $view['chart']['categories']);
        $this->assertSame(1456.0, $view['chart']['values'][1]);
    }

    public function testWithoutProjectionHasNoTablesNorChart(): void
    {
        $view = (new MitigationPlanView())->build([], [MitigationEmissionSourceMother::naturalGas()], null);

        $this->assertSame([], $view['actions']);
        $this->assertNull($view['projection']);
        $this->assertNull($view['chart']);
    }
}
