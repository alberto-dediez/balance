<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
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
            yield $theme . ' property es' => [$theme, 'property', Locale::Spanish];
            yield $theme . ' company es' => [$theme, 'company', Locale::Spanish];
            yield $theme . ' property mitigation es' => [$theme, 'property-mitigation', Locale::Spanish];
            yield $theme . ' property mitigation en' => [$theme, 'property-mitigation', Locale::English];
            yield $theme . ' company mitigation es' => [$theme, 'company-mitigation', Locale::Spanish];
            yield $theme . ' property long mitigation es' => [$theme, 'property-long-mitigation', Locale::Spanish];
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

    /** @return array{0: CarbonFootprintReportData, 1: CarbonFootprintReportTexts} */
    private function fixtureFor(string $type): array
    {
        return match ($type) {
            'event' => [EventCarbonFootprintReportDataMother::withCompensation(), CarbonFootprintReportTextsMother::forEvent()],
            'property' => [OrganizationalCarbonFootprintReportDataMother::forProperty(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-mitigation' => [OrganizationalCarbonFootprintReportDataMother::withMitigationPlan(), CarbonFootprintReportTextsMother::forOrganizationWithMitigationPlan()],
            'company-mitigation' => [OrganizationalCarbonFootprintReportDataMother::withMitigationPlan(CarbonFootprintReportSubjectType::Company), CarbonFootprintReportTextsMother::forOrganizationWithMitigationPlan()],
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
            foreach (['messages', 'scope', 'source', 'phase', 'exclusionReason', 'eventSectorType', 'eventTypologyType', 'eventSizeType'] as $domain) {
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
