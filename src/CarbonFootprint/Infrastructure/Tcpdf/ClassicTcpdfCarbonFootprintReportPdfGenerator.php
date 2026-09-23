<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Tcpdf\GenericGenerator;
use App\Shared\Infrastructure\Tcpdf\PdfKeyFigure;
use App\Shared\Infrastructure\Tcpdf\ReportTcpdf;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ClassicTcpdfCarbonFootprintReportPdfGenerator implements CarbonFootprintReportPdfGenerator
{
    use GenericGenerator;
    use CarbonFootprintReportFiguresTrait;

    private const float PAGE_WIDTH = 297;
    private const float PAGE_HEIGHT = 210;
    private const float MARGIN_LEFT = 16;
    private const float TEXT_TOP = 38;
    private const float TEXT_WIDTH = 135;
    private const float TABLE_LEFT = 20;
    private const float TABLE_WIDTH = 257;
    private const float BREAK_MARGIN = 16;
    private const float DOTS_TOP = 196;
    private const float DOTS_LEFT_X = 6;
    private const float DOTS_RIGHT_X = 229;
    private const int DOTS_COLUMNS = 9;
    private const int DOTS_ROWS = 4;
    private const float DOTS_GAP_X = 4.37;
    private const float DOTS_GAP_Y = 3.9;
    private const string DOTS_COLOR = '#9FB2BD';
    private const string IMAGES_DIR = 'carbon-footprint-report/classic/';

    private ?string $chromeBackground = null;
    private ?bool $chromeDotsOnLeft = true;
    private ?string $chromeSidePhoto = null;
    private string $chromeHeader = '';
    private int $backgroundIndex = 0;
    private int $tableNumber = 0;
    private int $currentYear = 0;

    public function __construct(private readonly TranslatorInterface $translator) {}

    public function generate(
        CarbonFootprintReportData $data,
        CarbonFootprintReportTexts $texts,
        CarbonFootprintReportCharts $charts,
        Locale $locale,
        string $themeSlug,
    ): string {
        $previousLocale = LocaleGetter::locale();
        LocaleGetter::setLocale($locale);
        $this->backgroundIndex = 0;
        $this->tableNumber = 0;
        $this->currentYear = $data->calculationYear();

        try {
            $pdf = $this->createPDF($data->subjectName(), null, 'L');

            $documentName = $this->trans('Informe de huella de carbono', $locale);
            $this->chromeHeader = sprintf('%s  |  %s %d', $data->subjectName(), $documentName, $data->calculationYear());

            $this->renderClassicCover($pdf, $data, $locale);
            $this->renderClassicFirstPage($pdf, $data, $locale);

            $pdf->setPageChrome(fn() => $this->paintPageChrome($pdf));

            match (true) {
                $data instanceof EventCarbonFootprintReportData => $this->renderEventReport($pdf, $data, $texts, $locale),
                $data instanceof OrganizationalCarbonFootprintReportData => $this->renderOrganizationalReport($pdf, $data, $texts, $locale),
                default => throw new \InvalidArgumentException(sprintf('Unsupported report data "%s".', $data::class)),
            };

            $this->renderClassicBackCover($pdf, $locale);

            $this->useDottedChrome(true, null);
            $this->useDataMargins($pdf);
            $this->renderTableOfContents($pdf, $this->trans('Índice', $locale), 3, $this->renderPageTitle());

            $path = sys_get_temp_dir() . '/' . uniqid('cfp-report-classic-', true) . '-' . $locale->value . '.pdf';
            $this->savePDF($pdf, $path);

            return $path;
        } finally {
            LocaleGetter::setLocale($previousLocale);
        }
    }

    private function renderEventReport(ReportTcpdf $pdf, EventCarbonFootprintReportData $data, CarbonFootprintReportTexts $texts, Locale $locale): void
    {
        $this->renderClassicKeyFigures($pdf, $this->eventKeyFigures($data, $locale), $locale);
        $this->renderDataPage($pdf, $this->trans('Datos del evento', $locale), null, $this->buildClassicTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], $this->eventDataRows($data, $locale), ['40%', '60%']), $this->trans('Datos del evento', $locale), count($this->eventDataRows($data, $locale)), $locale);
        $this->renderTextPage($pdf, $this->trans('Resumen ejecutivo', $locale), $texts->get(CarbonFootprintReportSectionKey::ExecutiveSummary), 'purpose.jpg');
        $this->renderTextPage($pdf, $this->trans('Metodología de cálculo', $locale), $texts->get(CarbonFootprintReportSectionKey::Methodology), 'context.jpg');
        $this->renderEventExclusionsPage($pdf, $data, $locale);
        $this->renderFootprintPage($pdf, $data, $locale);
        $this->renderEventResultsPages($pdf, $data, $locale);
        $this->renderCompensationPages($pdf, $data, $locale);
        $this->renderTextPage($pdf, $this->trans('Fuentes de los factores de emisión', $locale), $texts->get(CarbonFootprintReportSectionKey::EmissionFactorSources), 'ods.jpg');
        $this->renderTextPage($pdf, $this->trans('Análisis y recomendaciones', $locale), $texts->get(CarbonFootprintReportSectionKey::AnalysisAndRecommendations), 'purpose.jpg');

        if ($texts->has(CarbonFootprintReportSectionKey::Compensation)) {
            $this->renderTextPage($pdf, $this->trans('Compensación de emisiones', $locale), $texts->get(CarbonFootprintReportSectionKey::Compensation), 'context.jpg');
        }

        $this->renderAnnexPage($pdf, $this->buildClassicTableHtml($this->eventAnnexHeader($locale), $this->eventAnnexRows($data, $locale)), $this->trans('Anexo: factores de emisión aplicados', $locale), $locale);

        $compensation = $data->compensation;

        if (null !== $compensation && $compensation->hasCompensations()) {
            $this->renderAnnexPage($pdf, $this->buildClassicTableHtml($this->traceabilityHeader($locale), $this->traceabilityRows($data, $locale)), $this->trans('Anexo: trazabilidad de las compensaciones', $locale), $locale);
        }
    }

    private function renderOrganizationalReport(ReportTcpdf $pdf, OrganizationalCarbonFootprintReportData $data, CarbonFootprintReportTexts $texts, Locale $locale): void
    {
        $isCompany = CarbonFootprintReportSubjectType::Company === $data->subjectType;

        $this->renderClassicKeyFigures($pdf, $this->organizationalKeyFigures($data, $locale), $locale);
        $this->renderTextPage($pdf, $this->trans('Propósito', $locale), $texts->get(CarbonFootprintReportSectionKey::Purpose), 'purpose.jpg');
        $this->renderTextPage($pdf, $this->trans('Contexto normativo', $locale), $texts->get(CarbonFootprintReportSectionKey::RegulatoryContext), 'context.jpg');
        $this->renderTextPage($pdf, $this->trans('Relación con los ODS', $locale), $texts->get(CarbonFootprintReportSectionKey::SustainableDevelopmentGoals), 'ods.jpg');
        $this->renderIncludedEmissionsPage($pdf, $data, $locale);

        if ([] !== $data->exclusionLines) {
            $header = [$this->trans('Alcance', $locale), $this->trans('Fuente', $locale), $this->trans('Motivo', $locale), $this->trans('Comentario', $locale)];
            $rows = $this->organizationalExclusionRows($data, $locale);
            $this->renderDataPage($pdf, $this->trans('Exclusiones de fuentes de emisión', $locale), null, $this->buildClassicTableHtml($header, $rows, ['15%', '25%', '20%', '40%']), $this->trans('Exclusiones de fuentes de emisión', $locale), count($rows), $locale);
        }

        $icons = [
            Source::FixedFacilities->value => 'fixed.jpg',
            Source::OwnVehicles->value => 'vehicles.jpg',
            Source::RefrigerantLeaks->value => 'refrigerant.jpg',
            Source::BuildingElectricity->value => 'electricity.jpg',
        ];

        foreach ($this->scopeSourceDefinitions($locale) as [$scope, $source, $title]) {
            $exclusion = $this->exclusionFor($data, $scope, $source);

            if (null !== $exclusion) {
                $reason = $this->translator->trans($exclusion->reason->value, [], 'exclusionReason', $locale->value);
                $tableHtml = $this->buildClassicTableHtml([$this->trans('Fuente excluida: {reason}', $locale, ['{reason}' => $reason])], []);
                $this->renderDataPage($pdf, $title, $icons[$source->value] ?? null, $tableHtml, $title, 0, $locale, isSourceData: true);

                continue;
            }

            $isElectricity = Source::BuildingElectricity === $source;

            [$header, $rows, $widths, $hasTotal] = $isElectricity
                ? $this->electricityTable($data, $locale, $isCompany)
                : $this->sourceTable($data, $scope, $source, $locale, $isCompany);

            $groupHeaderCells = [
                ['', $isCompany ? 2 : 1],
                [$this->trans('Factores', $locale), $isElectricity ? 2 : 4],
                [$this->trans('Emisiones', $locale), $isElectricity ? 2 : 1],
            ];

            $tableHtml = [] === $rows
                ? $this->buildClassicTableHtml([$this->trans('No hay datos reportados', $locale)], [])
                : $this->buildClassicTableHtml($header, $rows, $widths, $groupHeaderCells, $hasTotal);

            $this->renderDataPage($pdf, $title, $icons[$source->value] ?? null, $tableHtml, $title, count($rows), $locale, isSourceData: true);
        }

        $this->renderFootprintPage($pdf, $data, $locale);
        $this->renderOrganizationalResultsPages($pdf, $data, $locale);
        $this->renderTextPage($pdf, $this->trans('Conclusión', $locale), $texts->get(CarbonFootprintReportSectionKey::Conclusion), 'purpose.jpg');
        $this->renderAnnexPage($pdf, $this->buildClassicTableHtml($this->organizationalAnnexHeader($locale, $isCompany), $this->organizationalAnnexRows($data, $locale, $isCompany)), $this->trans('Anexo: factores de emisión aplicados', $locale), $locale);
    }

    private function renderClassicCover(ReportTcpdf $pdf, CarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->paintImage($pdf, 'background.png', 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT);

        $titleFontSize = 22;
        $this->setFont($pdf, $titleFontSize, self::FONT_STYLE_BOLD, self::COLOR_PRIMARY);
        while ($titleFontSize > 16 && $pdf->getNumLines($data->subjectName(), 200) > 1) {
            $titleFontSize -= 2;
            $this->setFont($pdf, $titleFontSize, self::FONT_STYLE_BOLD, self::COLOR_PRIMARY);
        }
        $pdf->setXY(70, 66);
        $pdf->MultiCell(200, 10, $data->subjectName(), 0, 'L');
        $subtitleTop = $pdf->GetY() + 2;

        $subtitle = match ($data->subjectType()) {
            CarbonFootprintReportSubjectType::Property => $this->trans('Informe de huella de carbono de la propiedad {year}', $locale, ['{year}' => (string) $data->calculationYear()]),
            CarbonFootprintReportSubjectType::Company => $this->trans('Informe de huella de carbono de la empresa {year}', $locale, ['{year}' => (string) $data->calculationYear()]),
            CarbonFootprintReportSubjectType::Event => $this->trans('Informe de huella de carbono del evento {year}', $locale, ['{year}' => (string) $data->calculationYear()]),
        };

        $this->fitFont($pdf, mb_strtoupper($subtitle), 200, 22, 16, self::FONT_STYLE_REGULAR, self::COLOR_PRIMARY);
        $pdf->setXY(70, $subtitleTop);
        $pdf->MultiCell(200, 10, mb_strtoupper($subtitle), 0, 'L');

        $this->paintLogo($pdf, 70, $pdf->GetY() + 6, 50);

        $this->setFont($pdf, 12, self::FONT_STYLE_REGULAR, self::COLOR_SECONDARY);
        $pdf->setXY(0, 180);
        $pdf->Cell(self::PAGE_WIDTH, 6, $this->trans('Fecha de emisión', $locale), 0, 0, 'C');

        $this->setFont($pdf, 12, self::FONT_STYLE_MEDIUM, self::COLOR_PRIMARY);
        $pdf->setXY(0, 188);
        $pdf->Cell(self::PAGE_WIDTH, 6, (new \DateTimeImmutable())->format('d/m/Y'), 0, 0, 'C');
    }

    private function renderClassicFirstPage(ReportTcpdf $pdf, CarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->paintDots($pdf, true);
        $this->paintImage($pdf, 'first-page.jpg', 152, 0, 145, 210);
        $this->paintLogo($pdf, 16, 23, 50);

        $this->setFont($pdf, 110, self::FONT_STYLE_BOLD, self::COLOR_SECONDARY);
        $pdf->setXY(20, 79);
        $pdf->Cell(30, 40, '1', 0, 0, 'L');

        $this->setFont($pdf, 22, self::FONT_STYLE_SEMIBOLD, self::COLOR_PRIMARY);
        $pdf->setFontSpacing(0.30);
        $pdf->setXY(45, 88);
        $pdf->MultiCell(90, 10, mb_strtoupper($this->trans('Informe de huella de carbono', $locale)), 0, 'L');
        $pdf->setXY(45, 108);
        $pdf->Cell(90, 10, (string) $data->calculationYear(), 0, 0, 'L');
        $pdf->setFontSpacing(0);
    }

    private function renderClassicBackCover(ReportTcpdf $pdf, Locale $locale): void
    {
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();
        $this->paintImage($pdf, 'background.png', 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT);
        $this->paintLogo($pdf, 123, 78, 50);

        $this->setFont($pdf, 14, self::FONT_STYLE_REGULAR, self::COLOR_PRIMARY);
        $pdf->setXY(0, 98);
        $pdf->Cell(self::PAGE_WIDTH, 8, mb_strtoupper($this->trans('Democratizamos la sostenibilidad', $locale)), 0, 0, 'C');
        $pdf->setPrintHeader(true);
    }

    /** @param list<PdfKeyFigure> $figures */
    private function renderClassicKeyFigures(ReportTcpdf $pdf, array $figures, Locale $locale): void
    {
        $this->useDottedChrome($this->nextDotsOnLeft(), null);
        $this->useDataMargins($pdf);
        $this->renderKeyFigures($pdf, $this->trans('Cifras clave', $locale), $figures, $this->renderPageTitle(true));
    }

    private function renderTextPage(ReportTcpdf $pdf, string $title, string $markdown, string $sidePhoto): void
    {
        $this->useDottedChrome($this->nextDotsOnLeft(), $sidePhoto);
        $pdf->SetMargins(self::MARGIN_LEFT, self::TEXT_TOP, self::PAGE_WIDTH - self::MARGIN_LEFT - self::TEXT_WIDTH, true);
        $pdf->SetAutoPageBreak(true, self::BREAK_MARGIN);
        $pdf->AddPage();

        $this->bookmark($pdf, $title);
        $this->paintTitle($pdf, $title);

        $pdf->setXY(self::MARGIN_LEFT, 74);
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, self::COLOR_PRIMARY);
        $pdf->setCellHeightRatio(1.65);
        $pdf->writeHTML($this->markdownToStyledHtml($markdown), true, false, true, false, '');
        $pdf->setCellHeightRatio(1.25);
    }

    private function renderIncludedEmissionsPage(ReportTcpdf $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $this->useDottedChrome(true, null);
        $this->useDataMargins($pdf);
        $pdf->AddPage();

        $title = $this->trans('Emisiones incluidas en cada alcance', $locale);
        $this->bookmark($pdf, $title);
        $this->setFont($pdf, 18, self::FONT_STYLE_SEMIBOLD, self::COLOR_PRIMARY);
        $pdf->setXY(self::MARGIN_LEFT, self::TEXT_TOP);
        $pdf->MultiCell(100, 8, $title, 0, 'L');

        $excluded = [];
        foreach ($data->exclusionLines as $line) {
            $excluded[$line->scope->value . '|' . $line->source->value] = true;
        }

        $icons = [Scope::Scope1->value => 'cars.jpg', Scope::Scope2->value => 'lighting.jpg'];
        $blockTop = 70.0;

        foreach (Scope::cases() as $scope) {
            $this->paintImage($pdf, $icons[$scope->value], 16, $blockTop - 5, 21, 21);

            $this->setFont($pdf, 11, self::FONT_STYLE_SEMIBOLD, self::COLOR_SECONDARY);
            $pdf->setXY(40, $blockTop);
            $pdf->Cell(20, 6, $this->translator->trans($scope->value, [], 'scope', $locale->value), 0, 0, 'L');

            $this->setFont($pdf, 11, self::FONT_STYLE_SEMIBOLD, self::COLOR_PRIMARY);
            $lineY = $blockTop;
            foreach ($scope->sections() as $section) {
                $label = $this->translator->trans($section->source->value, [], 'source', $locale->value);

                if (isset($excluded[$scope->value . '|' . $section->source->value])) {
                    $label .= ' ' . $this->trans('(excluida)', $locale);
                }

                $pdf->setXY(60, $lineY);
                $pdf->Cell(4, 6, '-', 0, 0, 'L');
                $pdf->MultiCell(56, 6, $label, 0, 'L', false, 1, 64, $lineY);
                $lineY = $pdf->GetY() + 1;
            }

            $blockTop = max($lineY, $blockTop + 21) + 6;
        }

        $this->setFont($pdf, 8, self::FONT_STYLE_REGULAR, self::COLOR_PRIMARY);
        $pdf->setXY(self::MARGIN_LEFT, max(145, $blockTop + 4));
        $pdf->MultiCell(100, 5, $this->trans('Los datos de alcance 1 y alcance 2 dependen del tipo de instalaciones y equipos de la organización.', $locale), 0, 'L');
        $pdf->setX(self::MARGIN_LEFT);
        $pdf->MultiCell(100, 5, $this->trans('No se incluyen las emisiones de alcance 3 porque la organización no dispone de los datos necesarios.', $locale), 0, 'L');

        $this->paintImage($pdf, 'included-emissions.jpg', 125, 57, 160, 140, true);
    }

    private function renderEventExclusionsPage(ReportTcpdf $pdf, EventCarbonFootprintReportData $data, Locale $locale): void
    {
        if ([] === $data->exclusionLines) {
            return;
        }

        $title = $this->trans('Exclusiones de fuentes de emisión', $locale);
        $header = [$this->trans('Fase', $locale), $this->trans('Fuente', $locale), $this->trans('Motivo', $locale), $this->trans('Comentario', $locale)];
        $rows = $this->eventExclusionRows($data, $locale);

        $this->renderDataPage($pdf, $title, null, $this->buildClassicTableHtml($header, $rows, ['15%', '25%', '20%', '40%']), $title, count($rows), $locale);
    }

    private function renderDataPage(ReportTcpdf $pdf, string $title, ?string $icon, string $tableHtml, string $caption, int $rowCount, Locale $locale, float $fontSize = 10, bool $isSourceData = false): void
    {
        $this->useDottedChrome($this->nextDotsOnLeft(), null);
        $this->useDataMargins($pdf);
        $pdf->AddPage();

        $this->bookmark($pdf, $title);
        $this->paintTitle($pdf, $this->trans('Huella de carbono {year}', $locale, ['{year}' => (string) $this->currentYear]), 130);

        $kicker = $isSourceData ? $this->trans('Datos de', $locale) : null;
        $heading = mb_strtoupper($title);

        if ($rowCount < 4) {
            if (null !== $icon) {
                $this->paintImage($pdf, $icon, 16, 73, 21, 21);
            }

            $headingTop = 80;

            if (null !== $kicker) {
                $this->setFont($pdf, 18, self::FONT_STYLE_SEMIBOLD, self::COLOR_PRIMARY);
                $pdf->setXY(40, $headingTop);
                $pdf->Cell(237, 8, $kicker, 0, 0, 'L');
                $headingTop += 7;
            }

            $this->fitFont($pdf, $heading, 237, 18, 12, self::FONT_STYLE_SEMIBOLD, self::COLOR_SECONDARY);
            $pdf->setXY(40, $headingTop);
            $pdf->Cell(237, 8, $heading, 0, 0, 'L');
            $tableTop = 110;
        } else {
            if (null !== $icon) {
                $this->paintImage($pdf, $icon, 260, 38, 21, 21);
            }

            $headingRight = null !== $icon ? 258 : self::PAGE_WIDTH - self::TABLE_LEFT;
            $headingLeft = 60;
            $headingWidth = $headingRight - $headingLeft;
            $headingTop = null !== $kicker ? 52 : 48;

            if (null !== $kicker) {
                $this->setFont($pdf, 18, self::FONT_STYLE_SEMIBOLD, self::COLOR_PRIMARY);
                $pdf->setXY($headingLeft, 45);
                $pdf->Cell($headingWidth, 7, $kicker, 0, 0, 'R');
            }

            $this->fitFont($pdf, $heading, $headingWidth, 18, 11, self::FONT_STYLE_SEMIBOLD, self::COLOR_SECONDARY);
            $pdf->setXY($headingLeft, $headingTop);
            $pdf->Cell($headingWidth, 7, $heading, 0, 0, 'R');
            $tableTop = 73;
        }

        $this->writeClassicTable($pdf, $tableHtml, $caption, $tableTop, $locale, $fontSize);
    }

    private function fitFont(\TCPDF $pdf, string $text, float $width, float $size, float $minSize, string $style, string $color): float
    {
        $this->setFont($pdf, $size, $style, $color);

        while ($size > $minSize && $pdf->GetStringWidth($text) > $width) {
            $size -= 0.5;
            $this->setFont($pdf, $size, $style, $color);
        }

        return $size;
    }

    private function renderAnnexPage(ReportTcpdf $pdf, string $tableHtml, string $caption, Locale $locale): void
    {
        $this->useImageChrome('no-background.jpg');
        $this->useDataMargins($pdf);
        $pdf->AddPage();

        $title = $this->trans('Anexo', $locale);
        $this->bookmark($pdf, $caption);
        $this->paintTitle($pdf, $title, 130);

        $this->writeClassicTable($pdf, $tableHtml, $caption, 62, $locale, 7.5);
    }

    private function writeClassicTable(ReportTcpdf $pdf, string $tableHtml, string $caption, float $top, Locale $locale, float $fontSize): void
    {
        ++$this->tableNumber;

        $pdf->setXY(self::TABLE_LEFT, $top);
        $this->setFont($pdf, $fontSize, self::FONT_STYLE_REGULAR, self::COLOR_PRIMARY);
        $pdf->writeHTML($tableHtml, true, false, true, false, '');

        $pdf->Ln(3);
        $this->setFont($pdf, 10, self::FONT_STYLE_BOLD, self::COLOR_PRIMARY);
        $pdf->setFont('', 'U', 10);
        $pdf->setX(self::TABLE_LEFT);
        $pdf->Cell(self::TABLE_WIDTH, 6, $this->trans('Tabla {number}. {caption}', $locale, ['{number}' => (string) $this->tableNumber, '{caption}' => $caption]), 0, 1, 'C');
    }

    private function renderFootprintPage(ReportTcpdf $pdf, CarbonFootprintReportData $data, Locale $locale): void
    {
        $this->useImageChrome('background-footprint.jpg');
        $this->useDataMargins($pdf);
        $pdf->AddPage();

        $footprintTitle = $this->trans('Huella de carbono {year}', $locale, ['{year}' => (string) $data->calculationYear()]);
        $this->bookmark($pdf, $footprintTitle);
        $this->paintTitle($pdf, $footprintTitle, 130);

        $pdf->setTextShadow(['enabled' => true, 'depth' => 0.5, 'angle' => 45, 'color' => [0, 0, 0], 'opacity' => 0.2, 'blend_mode' => 'Normal']);
        $total = mb_strtoupper($this->trans('{total} kg de CO₂eq', $locale, ['{total}' => $this->number($data->totalEmissions()->co2eq())]));
        $this->fitFont($pdf, $total, 115, 24, 14, self::FONT_STYLE_BOLD, self::COLOR_NEUTRAL_100);
        $pdf->setXY(24, 114);
        $pdf->Cell(115, 12, $total, 0, 0, 'L');

        if ($data instanceof EventCarbonFootprintReportData) {
            $perAttendee = mb_strtoupper($this->trans('{total} kg de CO₂eq', $locale, ['{total}' => $this->number($data->emissionsPerAttendee->co2eq())]));
            $this->fitFont($pdf, $perAttendee, 82, 20, 12, self::FONT_STYLE_BOLD, self::COLOR_NEUTRAL_100);
            $pdf->setXY(142, 134);
            $pdf->Cell(82, 10, $perAttendee, 0, 0, 'L');
            $this->setFont($pdf, 16, self::FONT_STYLE_BOLD, self::COLOR_NEUTRAL_100);
            $pdf->setXY(142, 144);
            $pdf->Cell(82, 8, mb_strtoupper($this->trans('Por asistente', $locale)), 0, 0, 'L');
        } elseif ($data instanceof OrganizationalCarbonFootprintReportData) {
            $scopeLines = [
                mb_strtoupper($this->trans('Alcance 1: {total} kg de CO₂eq', $locale, ['{total}' => $this->number(($data->emissionsByScope[Scope::Scope1->value] ?? new Emissions())->co2eq())])),
                mb_strtoupper($this->trans('Alcance 2: {total} kg de CO₂eq', $locale, ['{total}' => $this->number(($data->emissionsByScope[Scope::Scope2->value] ?? new Emissions())->co2eq())])),
            ];
            $fontSize = min(array_map(fn(string $line): float => $this->fitFont($pdf, $line, 82, 16, 10, self::FONT_STYLE_BOLD, self::COLOR_NEUTRAL_100), $scopeLines));
            $this->setFont($pdf, $fontSize, self::FONT_STYLE_BOLD, self::COLOR_NEUTRAL_100);

            foreach ($scopeLines as $index => $line) {
                $pdf->setXY(142, 134 + $index * 10);
                $pdf->Cell(82, 8, $line, 0, 0, 'L');
            }
        }

        $pdf->setTextShadow(['enabled' => false]);
    }

    private function renderEventResultsPages(ReportTcpdf $pdf, EventCarbonFootprintReportData $data, Locale $locale): void
    {
        $totalsRows = $this->emissionsRows($data->totalEmissions);
        $totalsRows[] = [$this->trans('CO₂eq por asistente (kg)', $locale), $this->number($data->emissionsPerAttendee->co2eq())];
        $this->renderDataPage($pdf, $this->trans('Resultados', $locale), 'cars.jpg', $this->buildClassicTableHtml([$this->trans('Magnitud', $locale), $this->trans('Valor', $locale)], $totalsRows), $this->trans('Resultados', $locale), count($totalsRows), $locale);

        $sourceRows = $this->sourceRows($data->emissionsBySource, Phase::cases(), $locale);
        $this->renderDataPage($pdf, $this->trans('Emisiones por fuente', $locale), 'cars.jpg', $this->buildClassicTableHtml([$this->trans('Fuente', $locale), 'CO₂eq (kg)'], $sourceRows), $this->trans('Emisiones por fuente', $locale), count($sourceRows), $locale);

        $phaseRows = $this->phaseRows($data, $locale);
        $this->renderDataPage($pdf, $this->trans('Emisiones por fase', $locale), 'cars.jpg', $this->buildClassicTableHtml([$this->trans('Fase', $locale), 'CO₂eq (kg)'], $phaseRows), $this->trans('Emisiones por fase', $locale), count($phaseRows), $locale);

        $matrixRows = $this->phaseSourceRows($data, $locale);
        $this->renderDataPage($pdf, $this->trans('Matriz fase × fuente', $locale), 'cars.jpg', $this->buildClassicTableHtml([$this->trans('Fase', $locale), $this->trans('Fuente', $locale), 'CO₂eq (kg)'], $matrixRows), $this->trans('Matriz fase × fuente', $locale), count($matrixRows), $locale, 7.5);
    }

    private function renderOrganizationalResultsPages(ReportTcpdf $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $totalsRows = $this->emissionsRows($data->totalEmissions);
        $totalsRows[] = [$this->trans('Electricidad por localización (kg CO₂eq)', $locale), $this->number($data->locationBasedElectricityEmissions->co2eq())];
        $this->renderDataPage($pdf, $this->trans('Resultados', $locale), null, $this->buildClassicTableHtml([$this->trans('Magnitud', $locale), $this->trans('Valor', $locale)], $totalsRows), $this->trans('Resultados', $locale), count($totalsRows), $locale);

        $scopeRows = $this->scopeRows($data, $locale);
        $this->renderDataPage($pdf, $this->trans('Emisiones por alcance', $locale), null, $this->buildClassicTableHtml([$this->trans('Alcance', $locale), 'CO₂eq (kg)'], $scopeRows), $this->trans('Emisiones por alcance', $locale), count($scopeRows), $locale);

        $sourceRows = $this->sourceRows($data->emissionsBySource, Scope::cases(), $locale);
        $this->renderDataPage($pdf, $this->trans('Emisiones por fuente', $locale), null, $this->buildClassicTableHtml([$this->trans('Fuente', $locale), 'CO₂eq (kg)'], $sourceRows), $this->trans('Emisiones por fuente', $locale), count($sourceRows), $locale);

        $matrixRows = $this->scopeSourceRows($data, $locale);
        $this->renderDataPage($pdf, $this->trans('Matriz alcance × fuente', $locale), null, $this->buildClassicTableHtml([$this->trans('Alcance', $locale), $this->trans('Fuente', $locale), 'CO₂eq (kg)'], $matrixRows), $this->trans('Matriz alcance × fuente', $locale), count($matrixRows), $locale);

        if ([] !== $data->emissionsByProperty) {
            $propertyRows = $this->propertyRows($data, $locale);
            $this->renderDataPage($pdf, $this->trans('Emisiones por propiedad', $locale), null, $this->buildClassicTableHtml([$this->trans('Propiedad', $locale), 'CO₂eq (kg)'], $propertyRows), $this->trans('Emisiones por propiedad', $locale), count($propertyRows), $locale);
        }
    }

    private function renderCompensationPages(ReportTcpdf $pdf, EventCarbonFootprintReportData $data, Locale $locale): void
    {
        $compensation = $data->compensation;

        if (null === $compensation || !$compensation->hasCompensations()) {
            return;
        }

        $title = $this->trans('Compensación de emisiones', $locale);
        $summaryRows = $this->compensationSummaryRows($data, $locale);
        $this->renderDataPage($pdf, $title, null, $this->buildClassicTableHtml([$this->trans('Magnitud', $locale), $this->trans('Valor', $locale)], $summaryRows, ['60%', '40%']), $title, count($summaryRows), $locale);

        if ([] !== $compensation->projectLines) {
            $rows = $this->compensationProjectRows($data, $locale);
            $this->renderDataPage($pdf, $this->trans('Proyectos de compensación', $locale), null, $this->buildClassicTableHtml($this->compensationProjectsHeader($locale), $rows, ['16%', '28%', '18%', '16%', '11%', '11%']), $this->trans('Proyectos de compensación', $locale), count($rows), $locale);
        }
    }

    /**
     * @param list<string>             $headerCells
     * @param list<list<string>>       $rows
     * @param list<string>|null        $columnWidths
     * @param list<array{string, int}> $groupHeaderCells
     */
    private function buildClassicTableHtml(array $headerCells, array $rows, ?array $columnWidths = null, array $groupHeaderCells = [], bool $lastRowIsTotal = false): string
    {
        $border = sprintf('border: 1px solid %s;', $this->pdfBranding()->primaryHex());
        $html = '<table cellpadding="6" cellspacing="0" bgcolor="white" style="width: 100%;"><thead>';

        if ([] !== $groupHeaderCells) {
            $html .= '<tr>';
            foreach ($groupHeaderCells as [$label, $span]) {
                $html .= sprintf('<th colspan="%d" style="%s color: %s; text-align: center;"><b>%s</b></th>', $span, $border, $this->pdfBranding()->secondaryHex(), htmlspecialchars($label));
            }
            $html .= '</tr>';
        }

        $html .= '<tr>';
        foreach ($headerCells as $index => $headerCell) {
            $width = null !== $columnWidths ? sprintf(' width: %s;', $columnWidths[$index]) : '';
            $html .= sprintf('<th style="%s color: %s; text-align: left;%s"><b>%s</b></th>', $border, $this->pdfBranding()->primaryHex(), $width, htmlspecialchars($headerCell));
        }
        $html .= '</tr></thead>';

        $totalRowIndex = $lastRowIsTotal ? array_key_last($rows) : null;

        foreach ($rows as $rowIndex => $row) {
            $isTotal = $rowIndex === $totalRowIndex;
            $html .= '<tr nobr="true">';
            foreach ($row as $cellIndex => $cell) {
                $width = null !== $columnWidths ? sprintf(' width: %s;', $columnWidths[$cellIndex]) : '';
                $color = $isTotal && 0 === $cellIndex ? $this->pdfBranding()->secondaryHex() : $this->pdfBranding()->primaryHex();
                $content = htmlspecialchars($cell);
                $html .= sprintf('<td style="%s color: %s;%s">%s</td>', $border, $color, $width, $isTotal ? '<b>' . $content . '</b>' : $content);
            }
            $html .= '</tr>';
        }

        return $html . '</table>';
    }

    private function renderPageTitle(bool $bookmark = false): \Closure
    {
        return function (\TCPDF $pdf, string $title) use ($bookmark): void {
            if ($bookmark) {
                $this->bookmark($pdf, $title);
            }

            $this->paintTitle($pdf, $title);
            $pdf->setY(62);
        };
    }

    private function paintTitle(\TCPDF $pdf, string $title, float $width = 100): void
    {
        $this->paintImage($pdf, 'fingerprint.jpg', 16, 38, 20, 20);
        $this->setFont($pdf, 24, self::FONT_STYLE_REGULAR, self::COLOR_SECONDARY);
        $pdf->setCellHeightRatio(1.1);
        $pdf->setXY(41, 40);
        $pdf->MultiCell($width, 10, $title, 0, 'L');
        $pdf->setCellHeightRatio(1.25);
        $pdf->setY(62);
    }

    private function paintPageChrome(ReportTcpdf $pdf): void
    {
        if (null !== $this->chromeBackground) {
            $this->paintImage($pdf, $this->chromeBackground, 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT);
        }

        if (null !== $this->chromeDotsOnLeft) {
            $this->paintDots($pdf, $this->chromeDotsOnLeft);
        }

        $this->setFont($pdf, 8, self::FONT_STYLE_SEMIBOLD, self::COLOR_PRIMARY);
        $pdf->setXY(self::MARGIN_LEFT, 23);
        $pdf->Cell(200, 5, $this->chromeHeader, 0, 0, 'L');

        $this->paintLogo($pdf, 235, 18, 50);

        if (null !== $this->chromeSidePhoto) {
            $this->paintImage($pdf, $this->chromeSidePhoto, 166, 57, 131, 153);
        }

        $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_PRIMARY);
        $pdf->setXY(271, 200);
        $pdf->Cell(20, 5, $pdf->getAliasNumPage() . ' / ' . $pdf->getAliasNbPages(), 0, 0, 'L');
    }

    private function useDottedChrome(bool $dotsOnLeft, ?string $sidePhoto): void
    {
        $this->chromeBackground = null;
        $this->chromeDotsOnLeft = $dotsOnLeft;
        $this->chromeSidePhoto = $sidePhoto;
    }

    private function useImageChrome(string $background): void
    {
        $this->chromeBackground = $background;
        $this->chromeDotsOnLeft = null;
        $this->chromeSidePhoto = null;
    }

    private function useDataMargins(ReportTcpdf $pdf): void
    {
        $pdf->SetMargins(self::TABLE_LEFT, self::TEXT_TOP, self::TABLE_LEFT, true);
        $pdf->SetAutoPageBreak(true, self::BREAK_MARGIN);
    }

    private function nextDotsOnLeft(): bool
    {
        $dotsOnLeft = 1 === $this->backgroundIndex % 2;
        ++$this->backgroundIndex;

        return $dotsOnLeft;
    }

    private function paintDots(\TCPDF $pdf, bool $onLeft): void
    {
        $left = $onLeft ? self::DOTS_LEFT_X : self::DOTS_RIGHT_X;
        $color = $this->hex2rgb(self::DOTS_COLOR) ?? [159, 178, 189];

        for ($row = 0; $row < self::DOTS_ROWS; ++$row) {
            for ($column = 0; $column < self::DOTS_COLUMNS; ++$column) {
                $pdf->Circle($left + $column * self::DOTS_GAP_X, self::DOTS_TOP + $row * self::DOTS_GAP_Y, 0.5, 0, 360, 'F', [], $color);
            }
        }
    }

    private function paintImage(\TCPDF $pdf, string $image, float $x, float $y, float $width, float $height, bool $fitbox = false): void
    {
        $path = $this->getImagePath(self::IMAGES_DIR . $image);

        if (!is_file($path)) {
            return;
        }

        $this->withoutPageBreak($pdf, static fn() => $pdf->Image($path, $x, $y, $width, $height, '', '', '', false, 300, '', false, false, 0, $fitbox));
    }

    private function withoutPageBreak(\TCPDF $pdf, \Closure $paint): void
    {
        $autoPageBreak = $pdf->getAutoPageBreak();
        $breakMargin = $pdf->getBreakMargin();
        $pdf->SetAutoPageBreak(false, 0);

        try {
            $paint();
        } finally {
            $pdf->SetAutoPageBreak($autoPageBreak, $breakMargin);
        }
    }

    private function paintLogo(\TCPDF $pdf, float $x, float $y, float $width): void
    {
        $path = $this->getImagePath('logo-dark.png');

        if (is_file($path)) {
            $this->withoutPageBreak($pdf, static fn() => $pdf->Image($path, $x, $y, $width, 0, '', '', '', false, 300));
        }
    }
}
