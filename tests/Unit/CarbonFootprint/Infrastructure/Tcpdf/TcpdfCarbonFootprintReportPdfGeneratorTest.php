<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\Tcpdf\ClassicTcpdfCarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Infrastructure\Tcpdf\TcpdfCarbonFootprintReportPdfGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportChartsMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportDataMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportDataMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

final class TcpdfCarbonFootprintReportPdfGeneratorTest extends TestCase
{
    private const string PREVIEW_DIRECTORY = 'var/carbon-footprint-report-preview';

    /** @return iterable<string, array{0: string, 1: string, 2: Locale}> */
    public static function previews(): iterable
    {
        foreach (['default', 'classic'] as $theme) {
            yield $theme . ' event es' => [$theme, 'event', Locale::Spanish];
            yield $theme . ' event en' => [$theme, 'event', Locale::English];
            yield $theme . ' event electricity without mix es' => [$theme, 'event-electricity-without-mix', Locale::Spanish];
            yield $theme . ' property es' => [$theme, 'property', Locale::Spanish];
            yield $theme . ' company es' => [$theme, 'company', Locale::Spanish];
            yield $theme . ' property mitigation es' => [$theme, 'property-mitigation', Locale::Spanish];
            yield $theme . ' property mitigation en' => [$theme, 'property-mitigation', Locale::English];
            yield $theme . ' company mitigation es' => [$theme, 'company-mitigation', Locale::Spanish];
            yield $theme . ' property long mitigation es' => [$theme, 'property-long-mitigation', Locale::Spanish];
            yield $theme . ' property scope 3 es' => [$theme, 'property-scope-3', Locale::Spanish];
            yield $theme . ' company scope 3 es' => [$theme, 'company-scope-3', Locale::Spanish];
            yield $theme . ' property scope 3 excluded es' => [$theme, 'property-scope-3-excluded', Locale::Spanish];
            yield $theme . ' company non consolidated properties es' => [$theme, 'company-non-consolidated', Locale::Spanish];
        }
    }

    /** @dataProvider previews */
    public function testItDrawsAReport(string $theme, string $type, Locale $locale): void
    {
        [$data, $texts] = $this->fixtureFor($type);

        $path = $this->generatorFor($theme)->generate($data, $texts, CarbonFootprintReportChartsMother::withFixturePng(), $locale, $theme);

        try {
            $this->assertFileExists($path);

            $contents = (string) file_get_contents($path);

            $this->assertStringStartsWith('%PDF-', $contents);
            $this->assertStringEndsWith("%%EOF\n", $contents);
            $this->assertGreaterThanOrEqual(8, preg_match_all('#/Type /Page[^s]#', $contents));

            copy($path, $this->previewPath($theme, $type . '-' . $locale->value));
        } finally {
            @unlink($path);
        }
    }

    /** @return iterable<string, array{0: string}> */
    public static function themes(): iterable
    {
        yield 'default' => ['default'];
        yield 'classic' => ['classic'];
    }

    /** @dataProvider themes */
    public function testOnlyTheScope3SourcesWithDataHaveTheirOwnPage(string $theme): void
    {
        $sources = $this->scope3PageSources($theme, OrganizationalCarbonFootprintReportDataMother::withScope3OnlyInPurchasedGoodsAndServices());

        $this->assertSame([Source::PurchasedGoodsAndServices], $sources);
    }

    /** @dataProvider themes */
    public function testExcludedScope3SourcesKeepTheirOwnPage(string $theme): void
    {
        $sources = $this->scope3PageSources($theme, OrganizationalCarbonFootprintReportDataMother::withScope3OnlyInPurchasedGoodsAndServices(Source::BusinessTravel));

        $this->assertEqualsCanonicalizing([Source::PurchasedGoodsAndServices, Source::BusinessTravel], $sources);
    }

    /** @dataProvider themes */
    public function testTheNonConsolidatedPropertiesHaveTheirOwnPageInACompanyReport(string $theme): void
    {
        $sources = $this->scope3PageSources($theme, OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties());

        $this->assertContains(Source::DownstreamLeasedAssets, $sources);
        $this->assertContains(Source::Franchises, $sources);

        $generator = $this->generatorFor($theme);

        /** @var array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} $table */
        $table = (new \ReflectionMethod($generator, 'nonConsolidatedPropertyTable'))->invoke($generator, OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties(), Source::DownstreamLeasedAssets, Locale::Spanish);

        $this->assertCount(2, $table[1]);
        $this->assertSame('Hotel Arrendado', $table[1][0][0]);
        $this->assertTrue($table[3]);
    }

    /** @dataProvider themes */
    public function testAPropertyReportLeavesOutTheCompanyOnlySources(string $theme): void
    {
        $generator = $this->generatorFor($theme);

        /** @var list<list<string>> $rows */
        $rows = (new \ReflectionMethod($generator, 'includedEmissionsRows'))->invoke($generator, OrganizationalCarbonFootprintReportDataMother::withScope3(), Locale::Spanish);
        /** @var list<list<string>> $companyRows */
        $companyRows = (new \ReflectionMethod($generator, 'includedEmissionsRows'))->invoke($generator, OrganizationalCarbonFootprintReportDataMother::withScope3(CarbonFootprintReportSubjectType::Company), Locale::Spanish);

        $franchises = $this->translator()->trans(Source::Franchises->value, [], 'source', Locale::Spanish->value);
        $leasedAssets = $this->translator()->trans(Source::DownstreamLeasedAssets->value, [], 'source', Locale::Spanish->value);

        $this->assertNotContains($franchises, array_column($rows, 1));
        $this->assertNotContains($leasedAssets, array_column($rows, 1));
        $this->assertContains($franchises, array_column($companyRows, 1));
        $this->assertCount(count($rows) + 2, $companyRows);
    }

    /** @dataProvider themes */
    public function testTheEventElectricityTableListsOneRowPerElectricityActivityPlusTheTotal(string $theme): void
    {
        $withCompensation = $this->eventElectricityTableRows($theme, EventCarbonFootprintReportDataMother::withCompensation());

        $this->assertCount(3, $withCompensation);
        $this->assertSame('400,00', $withCompensation[1][5]);
        $this->assertCount(0, $this->eventElectricityTableRows($theme, EventCarbonFootprintReportDataMother::random()));

        $withoutMix = $this->eventElectricityTableRows($theme, EventCarbonFootprintReportDataMother::withElectricityWithoutLocationBasedResult());

        $this->assertSame('---', $withoutMix[1][5]);
    }

    /** @dataProvider themes */
    public function testTheEventTotalsIncludeTheDualReportingRowsOnlyWithElectricity(string $theme): void
    {
        $this->assertSame([], $this->eventDualReportingRows($theme, EventCarbonFootprintReportDataMother::random()));
        $this->assertCount(3, $this->eventDualReportingRows($theme, EventCarbonFootprintReportDataMother::withCompensation()));

        $withoutMix = $this->eventDualReportingRows($theme, EventCarbonFootprintReportDataMother::withElectricityWithoutLocationBasedResult());

        $this->assertCount(4, $withoutMix);
        $this->assertSame(['Actividades de electricidad sin factor de mix eléctrico', '1'], $withoutMix[3]);
    }

    /** @dataProvider themes */
    public function testTheOrganizationalTotalsIncludeTheDualReportingRows(string $theme): void
    {
        $rows = $this->organizationalDualReportingRows($theme, OrganizationalCarbonFootprintReportDataMother::forProperty());

        $this->assertCount(3, $rows);
        $this->assertSame('2.500,00', $rows[0][1]);
        $this->assertSame('2.000,00', $rows[1][1]);
        $this->assertSame('3.430,00', $rows[2][1]);

        $withoutMix = $this->organizationalDualReportingRows($theme, OrganizationalCarbonFootprintReportDataMother::withElectricityWithoutLocationBasedResult());

        $this->assertCount(4, $withoutMix);
        $this->assertSame(['Actividades de electricidad sin factor de mix eléctrico', '1'], $withoutMix[3]);
    }

    /** @return list<list<string>> */
    private function organizationalDualReportingRows(string $theme, OrganizationalCarbonFootprintReportData $data): array
    {
        $generator = $this->generatorFor($theme);

        /** @var list<list<string>> $rows */
        $rows = (new \ReflectionMethod($generator, 'organizationalDualReportingRows'))->invoke($generator, $data, Locale::Spanish);

        return $rows;
    }

    /** @return list<list<string>> */
    private function eventElectricityTableRows(string $theme, EventCarbonFootprintReportData $data): array
    {
        $generator = $this->generatorFor($theme);

        /** @var array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} $table */
        $table = (new \ReflectionMethod($generator, 'eventElectricityTable'))->invoke($generator, $data, Locale::Spanish);

        return $table[1];
    }

    /** @return list<list<string>> */
    private function eventDualReportingRows(string $theme, EventCarbonFootprintReportData $data): array
    {
        $generator = $this->generatorFor($theme);

        /** @var list<list<string>> $rows */
        $rows = (new \ReflectionMethod($generator, 'eventDualReportingRows'))->invoke($generator, $data, Locale::Spanish);

        return $rows;
    }

    /** @return list<Source> */
    private function scope3PageSources(string $theme, OrganizationalCarbonFootprintReportData $data): array
    {
        $generator = $this->generatorFor($theme);

        /** @var list<array{0: Scope, 1: Source, 2: string}> $definitions */
        $definitions = (new \ReflectionMethod($generator, 'scopeSourceDefinitions'))->invoke($generator, $data, Locale::Spanish);

        return array_values(array_map(
            static fn(array $definition): Source => $definition[1],
            array_filter($definitions, static fn(array $definition): bool => Scope::Scope3 === $definition[0]),
        ));
    }

    /** @return array{0: CarbonFootprintReportData, 1: CarbonFootprintReportTexts} */
    private function fixtureFor(string $type): array
    {
        return match ($type) {
            'event' => [EventCarbonFootprintReportDataMother::withCompensation(), CarbonFootprintReportTextsMother::forEvent()],
            'event-electricity-without-mix' => [EventCarbonFootprintReportDataMother::withElectricityWithoutLocationBasedResult(), CarbonFootprintReportTextsMother::forEvent()],
            'property' => [OrganizationalCarbonFootprintReportDataMother::forProperty(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-mitigation' => [OrganizationalCarbonFootprintReportDataMother::withMitigationPlan(), CarbonFootprintReportTextsMother::forOrganizationWithMitigationPlan()],
            'company-mitigation' => [OrganizationalCarbonFootprintReportDataMother::withMitigationPlan(CarbonFootprintReportSubjectType::Company), CarbonFootprintReportTextsMother::forOrganizationWithMitigationPlan()],
            'property-scope-3' => [OrganizationalCarbonFootprintReportDataMother::withScope3(), CarbonFootprintReportTextsMother::forOrganization()],
            'company-scope-3' => [OrganizationalCarbonFootprintReportDataMother::withScope3(CarbonFootprintReportSubjectType::Company), CarbonFootprintReportTextsMother::forOrganization()],
            'company-non-consolidated' => [OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-scope-3-excluded' => [OrganizationalCarbonFootprintReportDataMother::withScope3Excluded(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-long-mitigation' => [OrganizationalCarbonFootprintReportDataMother::withMitigationPlan(targetYear: 2035), CarbonFootprintReportTextsMother::forOrganizationWithMitigationPlan()],
            default => [OrganizationalCarbonFootprintReportDataMother::forCompanyWithTwoProperties(), CarbonFootprintReportTextsMother::forOrganization()],
        };
    }

    private function generatorFor(string $theme): CarbonFootprintReportPdfGenerator
    {
        return 'classic' === $theme
            ? new ClassicTcpdfCarbonFootprintReportPdfGenerator($this->translator())
            : new TcpdfCarbonFootprintReportPdfGenerator($this->translator());
    }

    private function translator(): Translator
    {
        $translator = new Translator(Locale::Spanish->value);
        $translator->addLoader('xlf', new XliffFileLoader());

        foreach (Locale::cases() as $locale) {
            foreach (['messages', 'scope', 'source', 'activityType', 'phase', 'exclusionReason', 'eventSectorType', 'eventTypologyType', 'eventSizeType'] as $domain) {
                $catalogue = sprintf('%s/translations/%s+intl-icu.%s.xlf', $this->rootDirectory(), $domain, $locale->value);

                if (is_file($catalogue)) {
                    $translator->addResource('xlf', $catalogue, $locale->value, $domain . '+intl-icu');
                }
            }
        }

        return $translator;
    }

    private function previewPath(string $theme, string $name): string
    {
        $directory = $this->rootDirectory() . '/' . self::PREVIEW_DIRECTORY . '/' . $theme;

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            $this->fail(sprintf('Could not create the preview directory %s', $directory));
        }

        return $directory . '/' . $name . '.pdf';
    }

    private function rootDirectory(): string
    {
        return dirname(__DIR__, 5);
    }
}
