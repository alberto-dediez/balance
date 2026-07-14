<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Chart;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Infrastructure\Chart\ApexChartEventCarbonFootprintReportChartsGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Chart\InMemoryApexChartClient;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportDataMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class ApexChartEventCarbonFootprintReportChartsGeneratorTest extends TestCase
{
    public function testGeneratesThreeChartsWithExpectedShapes(): void
    {
        $client = new InMemoryApexChartClient();
        $sut = new ApexChartEventCarbonFootprintReportChartsGenerator($client, new FakeTranslator());

        $data = EventCarbonFootprintReportDataMother::random();
        $charts = $sut->generate($data, Locale::Spanish);

        $this->assertNotSame('', $charts->bySource);
        $this->assertNotSame('', $charts->byPhase);
        $this->assertNotSame('', $charts->byPhaseAndSource);

        $calls = $client->calls();
        $this->assertCount(3, $calls);

        $this->assertSame('donut', $calls[0]['chart']['type']);
        $this->assertSame([10.0], $calls[0]['series']);

        $this->assertSame('bar', $calls[1]['chart']['type']);
        $this->assertCount(4, $calls[1]['series'][0]['data']);

        $this->assertSame('bar', $calls[2]['chart']['type']);
        $this->assertTrue($calls[2]['chart']['stacked']);
        $this->assertCount(1, $calls[2]['series']);
    }

    public function testDonutUsesPlaceholderWhenAllSourcesAreZero(): void
    {
        $client = new InMemoryApexChartClient();
        $sut = new ApexChartEventCarbonFootprintReportChartsGenerator($client, new FakeTranslator());

        $data = EventCarbonFootprintReportDataMother::random();
        $emptySources = [];
        foreach ($data->emissionsBySource as $key => $emissions) {
            $emptySources[$key] = new Emissions();
        }

        $sut->generate(
            new EventCarbonFootprintReportData(
                eventName: $data->eventName,
                sector: $data->sector,
                typology: $data->typology,
                size: $data->size,
                estimatedAttendees: $data->estimatedAttendees,
                assembly: $data->assembly,
                celebration: $data->celebration,
                breakdown: $data->breakdown,
                emissionFactorGroupName: $data->emissionFactorGroupName,
                calculationYear: $data->calculationYear,
                totalEmissions: $data->totalEmissions,
                emissionsPerAttendee: $data->emissionsPerAttendee,
                emissionsBySource: $emptySources,
                emissionsByPhase: $data->emissionsByPhase,
                emissionsByPhaseAndSource: $data->emissionsByPhaseAndSource,
                activityLines: $data->activityLines,
            ),
            Locale::Spanish,
        );

        $calls = $client->calls();
        $this->assertSame([1], $calls[0]['series']);
        $this->assertSame(['N/A'], $calls[0]['labels']);
    }
}
