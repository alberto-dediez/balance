<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Domain\Model\Theme\ThemeRegistry;
use App\Shared\Infrastructure\Tcpdf\GenericGenerator;
use App\Shared\Infrastructure\Tcpdf\ReportTcpdf;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TcpdfCarbonFootprintReportPdfGenerator implements CarbonFootprintReportPdfGenerator
{
    use GenericGenerator;
    use CarbonFootprintReportFiguresTrait;
    use MaterialityAnnexFiguresTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ThemeRegistry $themeRegistry,
    ) {}

    public function generate(
        CarbonFootprintReportData $data,
        CarbonFootprintReportTexts $texts,
        CarbonFootprintReportCharts $charts,
        Locale $locale,
        string $themeSlug,
    ): string {
        $previousLocale = LocaleGetter::locale();
        LocaleGetter::setLocale($locale);

        try {
            $theme = $this->themeRegistry->get($themeSlug);
            $pdf = $this->createPDF($data->subjectName(), PdfBranding::bioscore()->withThemeLogos($theme->logo(), $theme->inverseLogo()));

            match (true) {
                $data instanceof EventCarbonFootprintReportData => $this->renderEventReport($pdf, $data, $texts, $charts, $locale),
                $data instanceof OrganizationalCarbonFootprintReportData => $this->renderOrganizationalReport($pdf, $data, $texts, $charts, $locale),
                default => throw new \InvalidArgumentException(sprintf('Unsupported report data "%s".', $data::class)),
            };

            $this->renderTableOfContents($pdf, $this->trans('Índice', $locale));

            $path = sys_get_temp_dir() . '/' . uniqid('cfp-report-', true) . '-' . $locale->value . '.pdf';
            $this->savePDF($pdf, $path);

            return $path;
        } finally {
            LocaleGetter::setLocale($previousLocale);
        }
    }

    private function renderEventReport(ReportTcpdf $pdf, EventCarbonFootprintReportData $data, CarbonFootprintReportTexts $texts, CarbonFootprintReportCharts $charts, Locale $locale): void
    {
        $documentName = $this->trans('Informe de Huella de Carbono', $locale);

        $this->renderCover(
            $pdf,
            subtitle: $this->trans('Informe de Huella de Carbono del Evento', $locale),
            title: $data->eventName,
            date: (new \DateTimeImmutable())->format('d / m / Y'),
            footerLeft: 'BIOSCORE.INFO',
            documentName: $documentName,
        );

        $this->startContentPages($pdf, $documentName);

        $this->renderKeyFigures($pdf, $this->trans('Cifras clave', $locale), $this->eventKeyFigures($data, $locale));
        $this->renderEventDataSection($pdf, $data, $locale);
        $this->renderTextSection($pdf, $this->trans('Resumen ejecutivo', $locale), $texts->get(CarbonFootprintReportSectionKey::ExecutiveSummary));
        $this->renderTextSection($pdf, $this->trans('Metodología de cálculo', $locale), $texts->get(CarbonFootprintReportSectionKey::Methodology));
        $this->renderEventResultsSection($pdf, $data, $charts, $locale);
        $this->renderCompensationSection($pdf, $data, $texts, $charts, $locale);
        $this->renderEventExclusionsSection($pdf, $data, $locale);
        $this->renderTextSection($pdf, $this->trans('Fuentes de los factores de emisión', $locale), $texts->get(CarbonFootprintReportSectionKey::EmissionFactorSources));
        $this->renderTextSection($pdf, $this->trans('Análisis y recomendaciones', $locale), $texts->get(CarbonFootprintReportSectionKey::AnalysisAndRecommendations));
        $this->renderEventAnnexSection($pdf, $data, $locale);
        $this->renderCompensationAnnexSection($pdf, $data, $locale);
    }

    private function renderOrganizationalReport(ReportTcpdf $pdf, OrganizationalCarbonFootprintReportData $data, CarbonFootprintReportTexts $texts, CarbonFootprintReportCharts $charts, Locale $locale): void
    {
        $documentName = $this->trans('Informe de Huella de Carbono', $locale) . ' ' . $data->calculationYear;

        $this->renderCover(
            $pdf,
            subtitle: CarbonFootprintReportSubjectType::Property === $data->subjectType
                ? $this->trans('Informe de huella de carbono de la propiedad', $locale)
                : $this->trans('Informe de huella de carbono de la empresa', $locale),
            title: $data->subjectName,
            date: (new \DateTimeImmutable())->format('d / m / Y'),
            footerLeft: 'BIOSCORE.INFO',
            documentName: $documentName,
        );

        $this->startContentPages($pdf, $documentName);

        $this->renderKeyFigures($pdf, $this->trans('Cifras clave', $locale), $this->organizationalKeyFigures($data, $locale));
        $this->renderOrganizationDataSection($pdf, $data, $locale);
        $this->renderTextSection($pdf, $this->trans('Propósito', $locale), $texts->get(CarbonFootprintReportSectionKey::Purpose));
        $this->renderTextSection($pdf, $this->trans('Contexto normativo', $locale), $texts->get(CarbonFootprintReportSectionKey::RegulatoryContext));
        $this->renderTextSection($pdf, $this->trans('Metodología de cálculo', $locale), $texts->get(CarbonFootprintReportSectionKey::Methodology));
        $this->renderBoundariesSection($pdf, $data, $locale);
        $this->renderIncludedEmissionsSection($pdf, $data, $locale);
        $this->renderCategoryMethodsSection($pdf, $data, $locale);
        $this->renderProviderContributionsSection($pdf, $data, $locale);
        $this->renderScopeSourceSections($pdf, $data, $locale);
        $this->renderOrganizationalResultsSection($pdf, $data, $charts, $locale);
        $this->renderEmissionFactorSourcesSection($pdf, $data, $texts, $locale);
        $this->renderUncertaintySection($pdf, $data, $texts, $locale);
        $this->renderMitigationPlanSection($pdf, $data, $texts, $charts, $locale);
        $this->renderTextSection($pdf, $this->trans('Relación con los ODS', $locale), $texts->get(CarbonFootprintReportSectionKey::SustainableDevelopmentGoals));
        $this->renderTextSection($pdf, $this->trans('Conclusión', $locale), $texts->get(CarbonFootprintReportSectionKey::Conclusion));
        $this->renderOrganizationalAnnexSection($pdf, $data, $locale);
        $this->renderMaterialityAnnexSection($pdf, $data, $texts, $locale);
    }

    private function renderEventDataSection(\TCPDF $pdf, EventCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Datos del evento', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $html = $this->buildTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], $this->eventDataRows($data, $locale), ['40%', '60%']);

        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function renderOrganizationDataSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Datos de la huella', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $html = $this->buildTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], $this->organizationDataRows($data, $locale), ['40%', '60%']);

        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function renderTextSection(\TCPDF $pdf, string $title, string $markdown): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $title);
        $this->setFont($pdf, 10.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $pdf->writeHTML($this->markdownToStyledHtml($markdown), true, false, true, false, '');
    }

    private function renderEventResultsSection(\TCPDF $pdf, EventCarbonFootprintReportData $data, CarbonFootprintReportCharts $charts, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Resultados', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $totalsRows = $this->emissionsRows($data->totalEmissions, $locale);
        $totalsRows[] = [$this->trans('CO₂eq por asistente (kg)', $locale), $this->number($data->emissionsPerAttendee->co2eq())];
        array_push($totalsRows, ...$this->eventDualReportingRows($data, $locale));
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Magnitud', $locale), $this->trans('Valor', $locale)], $totalsRows), true, false, true, false, '');

        $pdf->Ln(4);
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Fuente', $locale), 'CO₂eq (kg)'], $this->sourceRows($data->emissionsBySource, Phase::cases(), $locale)), true, false, true, false, '');
        $this->embedChart($pdf, $charts->get('bySource'));

        $pdf->AddPage();
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Fase', $locale), 'CO₂eq (kg)'], $this->phaseRows($data, $locale)), true, false, true, false, '');
        $this->embedChart($pdf, $charts->get('byPhase'));

        $pdf->AddPage();
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Fase', $locale), $this->trans('Fuente', $locale), 'CO₂eq (kg)'], $this->phaseSourceRows($data, $locale)), true, false, true, false, '');
        $this->embedChart($pdf, $charts->get('byPhaseAndSource'));

        [$header, $rows, $widths] = $this->eventElectricityTable($data, $locale);

        if ([] !== $rows) {
            $pdf->AddPage();
            $this->renderSectionTitle($pdf, $this->trans('Electricidad: método de mercado y de localización', $locale));
            $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->buildTableHtml($header, $rows, $widths), true, false, true, false, '');
        }
    }

    private function renderOrganizationalResultsSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, CarbonFootprintReportCharts $charts, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Resultados', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $totalsRows = $this->emissionsRows($data->totalEmissions, $locale);
        array_push($totalsRows, ...$this->organizationalDualReportingRows($data, $locale));
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Magnitud', $locale), $this->trans('Valor', $locale)], $totalsRows), true, false, true, false, '');

        $co2eqOnlyNote = $this->co2eqOnlyNote($data, $locale);

        if (null !== $co2eqOnlyNote) {
            $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->MultiCell(0, 5, $co2eqOnlyNote, 0, 'L');
            $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        }

        $pdf->Ln(4);
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Alcance', $locale), 'CO₂eq (kg)', $this->trans('% del total', $locale)], $this->scopeRows($data, $locale)), true, false, true, false, '');
        $this->embedChart($pdf, $charts->get('byScope'));

        $pdf->AddPage();
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Fuente', $locale), 'CO₂eq (kg)', $this->trans('% del total', $locale)], $this->sourceRows($data->emissionsBySource, Scope::cases(), $locale, $data->totalEmissions)), true, false, true, false, '');
        $this->embedChart($pdf, $charts->get('bySource'));

        $pdf->AddPage();
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Alcance', $locale), $this->trans('Fuente', $locale), 'CO₂eq (kg)', $this->trans('% del total', $locale)], $this->scopeSourceRows($data, $locale)), true, false, true, false, '');

        if ([] !== $data->emissionsByProperty) {
            $pdf->Ln(4);
            $pdf->writeHTML($this->buildTableHtml([$this->trans('Propiedad', $locale), 'CO₂eq (kg)'], $this->propertyRows($data, $locale)), true, false, true, false, '');
            $this->embedChart($pdf, $charts->get('byProperty'));
        }
    }

    private function renderBoundariesSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Límites del inventario y enfoque de consolidación', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $pdf->writeHTML($this->buildTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], $this->boundaryRows($data, $locale), ['40%', '60%']), true, false, true, false, '');

        $propertyRows = $this->boundaryPropertyRows($data, $locale);

        if ([] === $propertyRows) {
            return;
        }

        $pdf->Ln(4);
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Propiedad', $locale), $this->trans('Régimen de explotación', $locale), $this->trans('Tratamiento', $locale)], $propertyRows, ['35%', '25%', '40%']), true, false, true, false, '');
    }

    private function renderCategoryMethodsSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Métodos de cálculo por categoría', $locale));
        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $header = [
            $this->trans('Alcance', $locale),
            $this->trans('Categoría', $locale),
            $this->trans('Método', $locale),
            $this->trans('Emisiones kgCO2eq', $locale),
            $this->trans('% de la categoría', $locale),
        ];

        $pdf->writeHTML($this->buildTableHtml($header, $this->categoryMethodRows($data, $locale), ['12%', '30%', '28%', '16%', '14%']), true, false, true, false, '');
    }

    private function renderProviderContributionsSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        if ($data->scope3Excluded) {
            return;
        }

        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Aportaciones de proveedores', $locale));
        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        [$header, $rows, $widths] = $this->providerContributionRows($data, $locale);

        if ([] === $rows) {
            $pdf->writeHTML($this->markdownToStyledHtml($this->trans('No se han registrado aportaciones de proveedores', $locale)), true, false, true, false, '');

            return;
        }

        $pdf->writeHTML($this->buildTableHtml($header, $rows, $widths), true, false, true, false, '');
        $pdf->Ln(2);
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], [$this->supplierDataShareRow($data, $locale)], ['60%', '40%']), true, false, true, false, '');
        $pdf->MultiCell(0, 5, $this->trans('Las aportaciones descartadas no se contabilizan en el inventario', $locale), 0, 'L');
    }

    private function renderEmissionFactorSourcesSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, CarbonFootprintReportTexts $texts, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Fuentes de los factores de emisión', $locale));
        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $header = [
            $this->trans('Fuente del factor', $locale),
            $this->trans('Años', $locale),
            $this->trans('Líneas', $locale),
            $this->trans('Emisiones kgCO2eq', $locale),
        ];

        $pdf->writeHTML($this->buildTableHtml($header, $this->emissionFactorSourceRows($data, $locale), ['40%', '20%', '15%', '25%']), true, false, true, false, '');

        $pdf->Ln(4);
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], $this->emissionFactorGroupRows($data, $locale), ['60%', '40%']), true, false, true, false, '');

        $pdf->Ln(4);
        $this->setFont($pdf, 10.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->writeHTML($this->markdownToStyledHtml($texts->get(CarbonFootprintReportSectionKey::EmissionFactorSources)), true, false, true, false, '');
    }

    private function renderUncertaintySection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, CarbonFootprintReportTexts $texts, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Incertidumbre y calidad de los datos', $locale));
        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $pdf->writeHTML($this->markdownToStyledHtml('**' . $this->trans('Encuesta de desplazamientos de empleados', $locale) . '**'), true, false, true, false, '');
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], $this->employeeSurveyRows($data, $locale), ['40%', '60%']), true, false, true, false, '');
        $pdf->Ln(4);

        $header = [
            $this->trans('Alcance', $locale),
            $this->trans('Categoría', $locale),
            $this->trans('Calidad de los datos', $locale),
            $this->trans('Observación', $locale),
        ];

        $pdf->writeHTML($this->buildTableHtml($header, $this->dataQualityRows($data, $locale), ['14%', '34%', '18%', '34%']), true, false, true, false, '');

        $pdf->Ln(4);
        $this->setFont($pdf, 10.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->writeHTML($this->markdownToStyledHtml($texts->get(CarbonFootprintReportSectionKey::Uncertainty)), true, false, true, false, '');
    }

    private function renderIncludedEmissionsSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Emisiones incluidas en cada alcance', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $header = [
            $this->trans('Alcance', $locale),
            $this->trans('Fuente', $locale),
            $this->trans('Estado', $locale),
            $this->trans('Motivo', $locale),
            $this->trans('Comentario', $locale),
        ];

        $pdf->writeHTML($this->buildTableHtml($header, $this->includedEmissionsRows($data, $locale), ['14%', '30%', '14%', '18%', '24%']), true, false, true, false, '');

        $footnote = $this->materialityFootnote($data, $locale);

        if (null !== $footnote) {
            $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->MultiCell(0, 5, $footnote, 0, 'L');
        }

        if ($data->precalculated) {
            $pdf->Ln(4);
            $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->MultiCell(0, 5, $this->trans('Los resultados de alcance 1 y alcance 2 han sido aportados por la organización como emisiones ya calculadas, sin desglose por actividades.', $locale), 0, 'L');
        }
    }

    private function renderScopeSourceSections(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $isCompany = CarbonFootprintReportSubjectType::Company === $data->subjectType;

        foreach ($this->scopeSourceDefinitions($data, $locale) as [$scope, $source, $title]) {
            $pdf->AddPage();
            $this->renderSectionTitle($pdf, $title);
            $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

            $exclusion = $this->exclusionFor($data, $scope, $source);

            if (null !== $exclusion) {
                $pdf->writeHTML($this->markdownToStyledHtml($this->trans('Fuente excluida: {reason}', $locale, ['{reason}' => $this->translator->trans($exclusion->reason->value, [], 'exclusionReason', $locale->value)])), true, false, true, false, '');

                continue;
            }

            [$header, $rows, $widths] = $this->organizationalSourceTable($data, $scope, $source, $locale, $isCompany);

            if ([] === $rows) {
                $pdf->writeHTML($this->markdownToStyledHtml($this->trans('No hay datos reportados', $locale)), true, false, true, false, '');

                continue;
            }

            $pdf->writeHTML($this->buildTableHtml($header, $rows, $widths), true, false, true, false, '');

            if (in_array($source, Scope::Scope3->companyOnlySources(), true)) {
                $pdf->MultiCell(0, 5, $this->minimumBoundaryNote($locale), 0, 'L');
            }
        }
    }

    private function renderCompensationSection(\TCPDF $pdf, EventCarbonFootprintReportData $data, CarbonFootprintReportTexts $texts, CarbonFootprintReportCharts $charts, Locale $locale): void
    {
        $compensation = $data->compensation;

        if (null === $compensation || !$compensation->hasCompensations()) {
            return;
        }

        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Compensación de emisiones', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $pdf->writeHTML($this->buildTableHtml([$this->trans('Magnitud', $locale), $this->trans('Valor', $locale)], $this->compensationSummaryRows($data, $locale), ['60%', '40%']), true, false, true, false, '');

        if ([] !== $compensation->projectLines) {
            $pdf->Ln(4);
            $pdf->writeHTML($this->buildTableHtml($this->compensationProjectsHeader($locale), $this->compensationProjectRows($data, $locale), ['16%', '28%', '18%', '16%', '11%', '11%']), true, false, true, false, '');
        }

        $this->embedChart($pdf, $charts->get('compensation'));

        if ($texts->has(CarbonFootprintReportSectionKey::Compensation)) {
            $pdf->Ln(4);
            $this->setFont($pdf, 10.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->markdownToStyledHtml($texts->get(CarbonFootprintReportSectionKey::Compensation)), true, false, true, false, '');
        }
    }

    private function renderMitigationPlanSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, CarbonFootprintReportTexts $texts, CarbonFootprintReportCharts $charts, Locale $locale): void
    {
        $mitigationPlan = $data->mitigationPlan;

        if (null === $mitigationPlan) {
            return;
        }

        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Plan de mejora {year}', $locale, ['{year}' => (string) $data->calculationYear]));

        if ($texts->has(CarbonFootprintReportSectionKey::MitigationPlan)) {
            $this->setFont($pdf, 10.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->markdownToStyledHtml($texts->get(CarbonFootprintReportSectionKey::MitigationPlan)), true, false, true, false, '');
        }

        $tables = [
            [$this->trans('Reducción de emisiones esperada por año y fuente en %', $locale), $this->mitigationRateRows($mitigationPlan->projection, $locale)],
            [$this->trans('Reducción de emisiones esperada por año y fuente en kg CO₂ eq', $locale), $this->mitigationReductionRows($mitigationPlan->projection, $locale)],
            [$this->trans('Emisiones esperadas por año en kg CO₂ eq', $locale), $this->mitigationExpectedRows($mitigationPlan->projection, $locale)],
        ];

        foreach ($tables as [$title, [$header, $rows, $widths]]) {
            $pdf->Ln(4);
            $this->setFont($pdf, 10.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->markdownToStyledHtml('**' . $title . '**'), true, false, true, false, '');
            $this->setFont($pdf, 7, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->buildTableHtml($header, $rows, $widths), true, false, true, false, '');
        }

        $this->embedChart($pdf, $charts->get('mitigationProjection'));
    }

    private function renderCompensationAnnexSection(\TCPDF $pdf, EventCarbonFootprintReportData $data, Locale $locale): void
    {
        $compensation = $data->compensation;

        if (null === $compensation || !$compensation->hasCompensations()) {
            return;
        }

        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Anexo: trazabilidad de las compensaciones', $locale));

        $this->setFont($pdf, 7, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->writeHTML($this->buildTableHtml($this->traceabilityHeader($locale), $this->traceabilityRows($data, $locale), ['11%', '8%', '9%', '13%', '13%', '9%', '7%', '15%', '15%']), true, false, true, false, '');
    }

    private function renderEventExclusionsSection(\TCPDF $pdf, EventCarbonFootprintReportData $data, Locale $locale): void
    {
        if ([] === $data->exclusionLines) {
            return;
        }

        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Exclusiones de fuentes de emisión', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $header = [
            $this->trans('Fase', $locale),
            $this->trans('Fuente', $locale),
            $this->trans('Motivo', $locale),
            $this->trans('Comentario', $locale),
        ];

        $pdf->writeHTML($this->buildTableHtml($header, $this->eventExclusionRows($data, $locale), ['15%', '25%', '20%', '40%']), true, false, true, false, '');
    }

    private function embedChart(\TCPDF $pdf, ?string $png): void
    {
        if (null === $png) {
            return;
        }

        if ($pdf->GetY() > $pdf->getPageHeight() - 110) {
            $pdf->AddPage();
        }

        $pdf->Image('@' . $png, self::CONTENT_MARGIN_LEFT, $pdf->GetY() + 4, self::CONTENT_WIDTH, 0, 'PNG');
        $pdf->setY($pdf->getImageRBY() + 8);
    }

    private function renderEventAnnexSection(\TCPDF $pdf, EventCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Anexo: factores de emisión aplicados', $locale));

        $this->setFont($pdf, 7.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->writeHTML($this->buildTableHtml($this->eventAnnexHeader($locale), $this->eventAnnexRows($data, $locale), ['22%', '9%', '13%', '11%', '11%', '6%', '17%', '11%']), true, false, true, false, '');
    }

    private function renderOrganizationalAnnexSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Anexo: factores de emisión aplicados', $locale));

        $isCompany = CarbonFootprintReportSubjectType::Company === $data->subjectType;

        $this->setFont($pdf, 7.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->writeHTML($this->buildTableHtml($this->organizationalAnnexHeader($locale, $isCompany), $this->organizationalAnnexRows($data, $locale, $isCompany), $isCompany ? ['18%', '9%', '12%', '12%', '10%', '10%', '5%', '13%', '11%'] : ['22%', '9%', '13%', '11%', '11%', '6%', '17%', '11%']), true, false, true, false, '');
    }

    private function renderMaterialityAnnexSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, CarbonFootprintReportTexts $texts, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Anexo: análisis de materialidad del alcance 3', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $annex = $data->materialityAnnex;
        $statement = $this->materialityAnnexStatement($data, $locale);

        if (null !== $statement) {
            $pdf->writeHTML($this->markdownToStyledHtml($statement), true, false, true, false, '');
        }

        if (null === $annex) {
            return;
        }

        if (null === $statement) {
            $pdf->writeHTML($this->markdownToStyledHtml($this->materialityMethodologyParagraph($annex, $locale)), true, false, true, false, '');
        }

        if ($annex->hasAssessment()) {
            $pdf->writeHTML($this->buildTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], $this->materialityTraceabilityRows($annex, $locale), ['40%', '60%']), true, false, true, false, '');
        }

        if (null !== $statement && !$annex->hasDecisions) {
            return;
        }

        if (null === $statement && $annex->showsAnswers()) {
            $pdf->Ln(4);
            $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->buildTableHtml($this->materialityQuestionHeader($locale), $this->materialityQuestionRows($annex, $locale), ['18%', '52%', '15%', '15%']), true, false, true, false, '');
        }

        $pdf->Ln(4);
        $this->setFont($pdf, 8, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->writeHTML($this->buildMaterialityMatrixHtml($annex, $locale, ['7%', '27%', '12%', '10%', '14%', '16%', '14%']), true, false, true, false, '');

        if ($data->scope3Excluded) {
            return;
        }

        if ($texts->has(CarbonFootprintReportSectionKey::MaterialityAnalysis)) {
            $pdf->Ln(4);
            $this->setFont($pdf, 10.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->markdownToStyledHtml('### ' . $this->materialityPlain($this->materialityNarrativeTitle($locale))), true, false, true, false, '');
            $pdf->writeHTML($this->markdownToStyledHtml($texts->get(CarbonFootprintReportSectionKey::MaterialityAnalysis)), true, false, true, false, '');
        }

        if ([] === $annex->justifications) {
            return;
        }

        $pdf->Ln(4);
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->writeHTML(sprintf('<h4>%s</h4>', htmlspecialchars($this->materialityEvidenceTitle($locale))), true, false, true, false, '');

        foreach ($annex->justifications as $justification) {
            $pdf->Ln(2);
            $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML(sprintf('<h5>%s</h5>', htmlspecialchars($this->materialityTopicLabel($justification->topic, $locale) . ' · ' . $this->trans('Excluida', $locale))), true, false, true, false, '');

            if ([] !== $justification->answers) {
                $this->setFont($pdf, 8.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
                $pdf->writeHTML($this->buildTableHtml($this->materialityAnswerHeader($locale), $this->materialityAnswerRows($justification, $locale), ['18%', '46%', '14%', '10%', '12%']), true, false, true, false, '');
            }

            $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->markdownToStyledHtml($this->materialityJustificationText($justification, $locale)), true, false, true, false, '');
        }
    }

    private function materialityMatrixHeaderCell(string $headerCell, string $width): string
    {
        return sprintf('<th style="background-color: %s; color: %s; text-align: left;%s">%s</th>', $this->pdfBranding()->darkHex(), $this->pdfBranding()->subtleHex(), $width, htmlspecialchars($headerCell));
    }
}
