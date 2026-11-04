<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Infrastructure\Tcpdf\TcpdfJustifiedExclusionsDocumentPdfGenerator;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportDataMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TcpdfJustifiedExclusionsDocumentPdfGeneratorTest extends TestCase
{
    private const string PREVIEW_DIRECTORY = 'var/carbon-footprint-report-preview';

    /** @return iterable<string, array{0: string, 1: Locale, 2: int}> */
    public static function documents(): iterable
    {
        yield 'property materiality es' => ['property-materiality', Locale::Spanish, 3];
        yield 'property materiality en' => ['property-materiality', Locale::English, 3];
        yield 'company materiality override es' => ['company-materiality-override', Locale::Spanish, 3];
        yield 'property materiality reopened es' => ['property-materiality-reopened', Locale::Spanish, 3];
        yield 'property scope 3 excluded materiality es' => ['property-scope-3-excluded-materiality', Locale::Spanish, 3];
        yield 'property without materiality es' => ['property-without-materiality', Locale::Spanish, 2];
    }

    /** @dataProvider documents */
    public function testItDrawsTheDocument(string $type, Locale $locale, int $minimumPages): void
    {
        $translator = $this->recordingTranslator();
        $path = new TcpdfJustifiedExclusionsDocumentPdfGenerator($translator)->generate($this->fixtureFor($type), $locale, PdfBranding::bioscore('Hotel Sol'));

        try {
            $this->assertFileExists($path);

            copy($path, $this->previewPath(sprintf('justified-exclusions-%s-%s', $type, $locale->value)));

            $contents = (string) file_get_contents($path);
            $pages = preg_match_all('#/Type /Page[^s]#', $contents);

            $this->assertStringStartsWith('%PDF-', $contents);
            $this->assertStringEndsWith("%%EOF\n", $contents);
            $this->assertGreaterThanOrEqual($minimumPages, $pages);

            if (2 === $minimumPages) {
                $this->assertSame(2, $pages);
            }

            $this->assertNotContains('Análisis', $translator->ids);
            $this->assertNotContains('Evidencias por categoría', $translator->ids);

            if ('property-without-materiality' !== $type) {
                $this->assertContains('El análisis sigue el GHG Protocol Scope 3 Standard (capítulo 6).', $translator->ids);
                $this->assertContains('Identificador de la evaluación', $translator->ids);
                $this->assertNotContains('Norma', $translator->ids);
            }
        } finally {
            @unlink($path);
        }
    }

    /** @return iterable<string, array{0: string}> */
    public static function sectionTitles(): iterable
    {
        yield 'annex' => ['Anexo: análisis de materialidad del alcance 3'];
        yield 'organization' => ['Datos de la organización y de la evaluación'];
        yield 'justification' => ['Justificación de cada exclusión del análisis de materialidad del alcance 3'];
    }

    /** @dataProvider sectionTitles */
    public function testTheSectionTitleKeepsTheOrderOfItsWords(string $title): void
    {
        $generator = new TcpdfJustifiedExclusionsDocumentPdfGenerator($this->translator());
        (new \ReflectionMethod($generator, 'createPDF'))->invoke($generator, 'Title', PdfBranding::bioscore('Hotel Sol'));

        $pdf = new class extends \TCPDF {
            /** @var list<string> */
            public array $texts = [];

            public function MultiCell(mixed $w, mixed $h, mixed $txt, mixed ...$arguments): mixed
            {
                $this->texts[] = (string) $txt;

                return parent::MultiCell($w, $h, $txt, ...$arguments);
            }
        };
        $pdf->AddPage();
        $pdf->texts = [];

        (new \ReflectionMethod($generator, 'renderSectionTitle'))->invoke($generator, $pdf, $title, false);

        $this->assertNotEmpty($pdf->texts);
        $this->assertStringContainsString("\n", $pdf->texts[0]);
        $this->assertSame($title, str_replace("\n", ' ', $pdf->texts[0]));
    }

    private function fixtureFor(string $type): OrganizationalCarbonFootprintReportData
    {
        return match ($type) {
            'property-materiality' => OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessment(),
            'company-materiality-override' => OrganizationalCarbonFootprintReportDataMother::withMaterialityOverride(CarbonFootprintReportSubjectType::Company),
            'property-materiality-reopened' => OrganizationalCarbonFootprintReportDataMother::withReopenedMaterialityAssessment(),
            'property-scope-3-excluded-materiality' => OrganizationalCarbonFootprintReportDataMother::withScope3ExcludedAndMaterialityDecisions(),
            default => OrganizationalCarbonFootprintReportDataMother::withoutMaterialityAssessment(),
        };
    }

    /** @return TranslatorInterface&object{ids: list<string>} */
    private function recordingTranslator(): TranslatorInterface
    {
        return new class ($this->translator()) implements TranslatorInterface {
            /** @var list<string> */
            public array $ids = [];

            public function __construct(private readonly Translator $translator) {}

            /** @param array<string, mixed> $parameters */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                $this->ids[] = $id;

                return $this->translator->trans($id, $parameters, $domain, $locale);
            }

            public function getLocale(): string
            {
                return $this->translator->getLocale();
            }
        };
    }

    private function translator(): Translator
    {
        $translator = new Translator(Locale::Spanish->value);
        $translator->addLoader('xlf', new XliffFileLoader());

        foreach (Locale::cases() as $locale) {
            foreach (['messages', 'scope', 'source', 'exclusionReason', 'materialityVerdictType', 'materialityCategoryStateType', 'materialityCriterionType', 'materialityAnswerValueType', 'materialityAnswerScaleType'] as $domain) {
                $catalogue = sprintf('%s/translations/%s+intl-icu.%s.xlf', $this->rootDirectory(), $domain, $locale->value);

                if (is_file($catalogue)) {
                    $translator->addResource('xlf', $catalogue, $locale->value, $domain . '+intl-icu');
                }
            }
        }

        return $translator;
    }

    private function previewPath(string $name): string
    {
        $directory = $this->rootDirectory() . '/' . self::PREVIEW_DIRECTORY;

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
