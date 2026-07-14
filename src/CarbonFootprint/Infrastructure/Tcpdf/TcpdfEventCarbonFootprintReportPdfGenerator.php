<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTexts;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TcpdfEventCarbonFootprintReportPdfGenerator implements EventCarbonFootprintReportPdfGenerator
{
    use GenericGenerator;

    public function __construct(private readonly TranslatorInterface $translator) {}

    public function generate(
        EventCarbonFootprintReportData $data,
        EventCarbonFootprintReportTexts $texts,
        EventCarbonFootprintReportCharts $charts,
        Locale $locale,
    ): string {
        $previousLocale = LocaleGetter::locale();
        LocaleGetter::setLocale($locale);

        try {
            $documentName = $this->trans('Informe de Huella de Carbono', $locale);

            $pdf = $this->createPDF($data->eventName);

            $this->renderCover(
                $pdf,
                subtitle: $this->trans('Informe de Huella de Carbono del Evento', $locale),
                title: $data->eventName,
                date: (new \DateTimeImmutable())->format('d / m / Y'),
                footerLeft: 'BIOSCORE.INFO',
                documentName: $documentName,
            );

            $this->startContentPages($pdf, $documentName);

            $this->renderEventDataSection($pdf, $data, $locale);
            $this->renderTextSection($pdf, $this->trans('Resumen ejecutivo', $locale), $texts->executiveSummary);
            $this->renderTextSection($pdf, $this->trans('Metodología de cálculo', $locale), $texts->methodology);
            $this->renderResultsSection($pdf, $data, $charts, $locale);
            $this->renderTextSection($pdf, $this->trans('Fuentes de los factores de emisión', $locale), $texts->emissionFactorSources);
            $this->renderTextSection($pdf, $this->trans('Análisis y recomendaciones', $locale), $texts->analysisAndRecommendations);
            $this->renderAnnexSection($pdf, $data, $locale);

            $path = sys_get_temp_dir() . '/' . uniqid('ecf-report-', true) . '-' . $locale->value . '.pdf';
            $this->savePDF($pdf, $path);

            return $path;
        } finally {
            LocaleGetter::setLocale($previousLocale);
        }
    }

    private function trans(string $id, Locale $locale): string
    {
        return $this->translator->trans($id, [], 'messages', $locale->value);
    }

    private function renderEventDataSection(\TCPDF $pdf, EventCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Datos del evento', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);

        $rows = [
            [$this->trans('Nombre del evento', $locale), $data->eventName],
            [$this->trans('Sector', $locale), null !== $data->sector ? $this->translator->trans($data->sector->value, [], 'eventSectorType', $locale->value) : '-'],
            [$this->trans('Tipología', $locale), null !== $data->typology ? $this->translator->trans($data->typology->value, [], 'eventTypologyType', $locale->value) : '-'],
            [$this->trans('Tamaño', $locale), null !== $data->size ? $this->translator->trans($data->size->value, [], 'eventSizeType', $locale->value) : '-'],
            [$this->trans('Asistentes estimados', $locale), null !== $data->estimatedAttendees ? (string) $data->estimatedAttendees : '-'],
            [$this->trans('Montaje', $locale), (string) $data->assembly],
            [$this->trans('Celebración', $locale), (string) $data->celebration],
            [$this->trans('Desmontaje', $locale), (string) $data->breakdown],
            [$this->trans('Año de cálculo', $locale), (string) $data->calculationYear],
        ];

        $html = $this->buildTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], $rows, ['40%', '60%']);

        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function renderTextSection(\TCPDF $pdf, string $title, string $markdown): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $title);
        $this->setFont($pdf, 10.5, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);

        $pdf->writeHTML($this->markdownToStyledHtml($markdown), true, false, true, false, '');
    }

    private function renderResultsSection(\TCPDF $pdf, EventCarbonFootprintReportData $data, EventCarbonFootprintReportCharts $charts, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Resultados', $locale));
        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);

        $totalsRows = [
            ['CO₂eq (kg)', number_format((float) $data->totalEmissions->co2eq(), 2, ',', '.')],
            ['CO₂ (kg)', number_format((float) $data->totalEmissions->co2(), 2, ',', '.')],
            ['CH₄ (kg)', number_format((float) $data->totalEmissions->ch4(), 2, ',', '.')],
            ['N₂O (kg)', number_format((float) $data->totalEmissions->n2o(), 2, ',', '.')],
            [$this->trans('CO₂eq por asistente (kg)', $locale), number_format((float) $data->emissionsPerAttendee->co2eq(), 2)],
        ];
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Magnitud', $locale), $this->trans('Valor', $locale)], $totalsRows), true, false, true, false, '');

        $sourceRows = [];
        foreach (Source::cases() as $source) {
            $sourceRows[] = [$this->translator->trans($source->value, [], 'source', $locale->value), number_format((float) $data->emissionsBySource[$source->value]->co2eq(), 2, ',', '.')];
        }
        $pdf->Ln(4);
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Fuente', $locale), 'CO₂eq (kg)'], $sourceRows), true, false, true, false, '');
        $this->embedChart($pdf, $charts->bySource);

        $phaseRows = [];
        foreach (Phase::cases() as $phase) {
            $phaseRows[] = [$this->translator->trans($phase->value, [], 'phase', $locale->value), number_format((float) $data->emissionsByPhase[$phase->value]->co2eq(), 2, ',', '.')];
        }
        $pdf->AddPage();
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Fase', $locale), 'CO₂eq (kg)'], $phaseRows), true, false, true, false, '');
        $this->embedChart($pdf, $charts->byPhase);

        $matrixRows = [];
        foreach (Phase::cases() as $phase) {
            foreach (Source::cases() as $source) {
                $matrixRows[] = [
                    $this->translator->trans($phase->value, [], 'phase', $locale->value),
                    $this->translator->trans($source->value, [], 'source', $locale->value),
                    number_format((float) $data->emissionsByPhaseAndSource[$phase->value][$source->value]->co2eq(), 2, ',', '.'),
                ];
            }
        }
        $pdf->AddPage();
        $pdf->writeHTML($this->buildTableHtml([$this->trans('Fase', $locale), $this->trans('Fuente', $locale), 'CO₂eq (kg)'], $matrixRows), true, false, true, false, '');
        $this->embedChart($pdf, $charts->byPhaseAndSource);
    }

    private function embedChart(\TCPDF $pdf, string $png): void
    {
        if ($pdf->GetY() > $pdf->getPageHeight() - 110) {
            $pdf->AddPage();
        }

        $pdf->Image('@' . $png, self::CONTENT_MARGIN_LEFT, $pdf->GetY() + 4, self::CONTENT_WIDTH, 0, 'PNG');
        $pdf->setY($pdf->getImageRBY() + 8);
    }

    private function renderAnnexSection(\TCPDF $pdf, EventCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Anexo: factores de emisión aplicados', $locale));

        $header = [
            $this->trans('Actividad', $locale),
            $this->trans('Fase', $locale),
            $this->trans('Fuente', $locale),
            $this->trans('Cantidad', $locale),
            $this->trans('Factor kgCO2eq/unidad', $locale),
            $this->trans('Año', $locale),
            $this->trans('Fuente del factor', $locale),
            $this->trans('Emisiones kgCO2eq', $locale),
        ];

        $rows = [];
        foreach ($data->activityLines as $activityLine) {
            $rows[] = [
                $activityLine->itemName,
                $this->translator->trans($activityLine->phase->value, [], 'phase', $locale->value),
                $this->translator->trans($activityLine->source->value, [], 'source', $locale->value),
                sprintf('%s %s', $activityLine->quantity, $activityLine->unit->value),
                $activityLine->factorCo2eq,
                (string) $activityLine->factorYear,
                $activityLine->factorSource ?? '-',
                number_format((float) $activityLine->emissionsCo2eq, 2, ',', '.'),
            ];
        }

        $this->setFont($pdf, 7.5, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $columnWidths = ['22%', '9%', '13%', '11%', '11%', '6%', '17%', '11%'];
        $pdf->writeHTML($this->buildTableHtml($header, $rows, $columnWidths), true, false, true, false, '');
    }
}
