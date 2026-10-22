<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Chart;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Infrastructure\Chart\ApexChartCarbonFootprintReportChartsGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Chart\InMemoryApexChartClient;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportDataMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportDataMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApexChartCarbonFootprintReportChartsGeneratorTest extends TestCase
{
    public function testGeneratesThreeEventChartsWithExpectedShapes(): void
    {
        $client = new InMemoryApexChartClient();
        $sut = new ApexChartCarbonFootprintReportChartsGenerator($client, new FakeTranslator());

        $charts = $sut->generate(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertNotNull($charts->get('bySource'));
        $this->assertNotNull($charts->get('byPhase'));
        $this->assertNotNull($charts->get('byPhaseAndSource'));
        $this->assertNull($charts->get('compensation'));

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
        $sut = new ApexChartCarbonFootprintReportChartsGenerator($client, new FakeTranslator());

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

    public function testGeneratesTheOrganizationalChartsOfAProperty(): void
    {
        $client = new InMemoryApexChartClient();
        $sut = new ApexChartCarbonFootprintReportChartsGenerator($client, new FakeTranslator());

        $charts = $sut->generate(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::Spanish);

        $this->assertSame(['bySource', 'byScope'], array_keys($charts->images));
        $this->assertNull($charts->get('byProperty'));

        $calls = $client->calls();
        $this->assertCount(2, $calls);
        $this->assertSame('donut', $calls[0]['chart']['type']);
        $this->assertSame([180.0, 2500.0, 1250.0], $calls[0]['series']);
        $this->assertSame('bar', $calls[1]['chart']['type']);
        $this->assertSame([1430.0, 2500.0, 0.0], $calls[1]['series'][0]['data']);
        $this->assertSame(['scope_1', 'scope_2', 'scope_3'], $calls[1]['xaxis']['categories']);
    }

    public function testGeneratesTheByPropertyChartOnlyForACompany(): void
    {
        $client = new InMemoryApexChartClient();
        $sut = new ApexChartCarbonFootprintReportChartsGenerator($client, new FakeTranslator());

        $charts = $sut->generate(OrganizationalCarbonFootprintReportDataMother::forCompanyWithTwoProperties(), Locale::Spanish);

        $this->assertNotNull($charts->get('byProperty'));

        $calls = $client->calls();
        $this->assertCount(3, $calls);
        $this->assertTrue($calls[2]['plotOptions']['bar']['horizontal']);
        $this->assertSame(['Hotel Sol', 'Hotel Luna', 'Sin asignar a propiedad'], $calls[2]['xaxis']['categories']);
        $this->assertSame([2680.0, 0.0, 1250.0], $calls[2]['series'][0]['data']);
    }

    public function testTheUnassignedPropertyBarIsLabelledInTheRequestedLocale(): void
    {
        $client = new InMemoryApexChartClient();
        $translator = new class implements TranslatorInterface {
            /** @param array<string, string> $parameters */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return sprintf('%s [%s]', $id, $locale);
            }

            public function getLocale(): string
            {
                return 'es';
            }
        };
        $sut = new ApexChartCarbonFootprintReportChartsGenerator($client, $translator);

        $sut->generate(OrganizationalCarbonFootprintReportDataMother::forCompanyWithTwoProperties(), Locale::English);

        $this->assertSame(['Hotel Sol', 'Hotel Luna', 'Sin asignar a propiedad [en]'], $client->calls()[2]['xaxis']['categories']);
    }
}
