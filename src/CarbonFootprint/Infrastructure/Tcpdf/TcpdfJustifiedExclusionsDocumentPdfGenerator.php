<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\JustifiedExclusionsDocumentPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnex;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Tcpdf\GenericGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TcpdfJustifiedExclusionsDocumentPdfGenerator implements JustifiedExclusionsDocumentPdfGenerator
{
    use GenericGenerator;
    use CarbonFootprintReportFiguresTrait;
    use MaterialityAnnexFiguresTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function generate(OrganizationalCarbonFootprintReportData $data, Locale $locale, PdfBranding $branding): string
    {
        $previousLocale = LocaleGetter::locale();
        LocaleGetter::setLocale($locale);

        try {
            $title = $this->trans('Exclusiones justificadas del alcance 3', $locale);
            $documentName = $title . ' ' . $data->calculationYear;
            $pdf = $this->createPDF($title, $branding, 'P');

            $this->renderCover(
                $pdf,
                subtitle: $title,
                title: $data->subjectName,
                date: (new \DateTimeImmutable())->format('d / m / Y'),
                footerLeft: sprintf('%s · %d', $data->companyName ?? $data->subjectName, $data->calculationYear),
                documentName: $documentName,
            );

            $this->startContentPages($pdf, $documentName);
            $pdf->AddPage();

            $this->renderOrganizationSection($pdf, $data, $locale);
            $this->renderScope3Section($pdf, $data, $locale);
            $this->renderMaterialitySections($pdf, $data, $locale);

            $path = sys_get_temp_dir() . '/' . uniqid('cfp-justified-exclusions-', true) . '-' . $locale->value . '.pdf';
            $this->savePDF($pdf, $path);

            return $path;
        } finally {
            LocaleGetter::setLocale($previousLocale);
        }
    }

    private function renderOrganizationSection(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $this->renderSectionTitle($pdf, $this->trans('Datos de la organización y de la evaluación', $locale));
        $this->setFont($pdf, 8.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        $rows = [[$this->trans('Organización', $locale), $data->subjectName]];

        if (null !== $data->companyName) {
            $rows[] = [$this->trans('Empresa', $locale), $data->companyName];
        }

        if (null !== $data->countryName) {
            $rows[] = [$this->trans('País', $locale), $data->countryName];
        }

        $rows[] = [$this->trans('Año', $locale), (string) $data->calculationYear];
        $rows[] = [$this->trans('Enfoque de consolidación', $locale), $this->trans('Control operacional', $locale)];

        $annex = $data->materialityAnnex;

        if (null !== $annex && !$annex->isAnalysisMissing()) {
            array_push($rows, ...$this->materialityTraceabilityRows($annex, $locale));
            $this->setFont($pdf, 9.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->markdownToStyledHtml($this->materialityMethodologyParagraph($annex, $locale)), true, false, true, false, '');
            $this->setFont($pdf, 8.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        }

        $pdf->writeHTML($this->buildTableHtml([$this->trans('Dato', $locale), $this->trans('Valor', $locale)], $rows, ['40%', '60%']), true, false, true, false, '');
    }

    private function renderScope3Section(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $pdf->Ln(4);
        $this->renderSectionTitle($pdf, $this->trans('Alcance 3 en el inventario', $locale));
        $this->setFont($pdf, 8, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

        if ($data->scope3Excluded) {
            $this->setFont($pdf, 9.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->markdownToStyledHtml((string) $this->materialityAnnexStatement($data, $locale)), true, false, true, false, '');

            return;
        }

        $scope3 = $this->translator->trans(Scope::Scope3->value, [], 'scope', $locale->value);
        $rows = array_values(array_filter($this->includedEmissionsRows($data, $locale), static fn(array $row): bool => $scope3 === $row[0]));
        $header = [$this->trans('Alcance', $locale), $this->trans('Fuente', $locale), $this->trans('Estado', $locale), $this->trans('Motivo', $locale), $this->trans('Comentario', $locale)];

        $pdf->writeHTML($this->buildTableHtml($header, $rows, ['14%', '30%', '14%', '18%', '24%']), true, false, true, false, '');
    }

    private function renderMaterialitySections(\TCPDF $pdf, OrganizationalCarbonFootprintReportData $data, Locale $locale): void
    {
        $annex = $data->materialityAnnex;

        if (null === $annex || $annex->isAnalysisMissing() || $annex->hasNoScope3Topics()) {
            if (!$data->scope3Excluded) {
                $pdf->Ln(2);
                $this->setFont($pdf, 9.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
                $pdf->writeHTML($this->markdownToStyledHtml((string) $this->materialityAnnexStatement($data, $locale)), true, false, true, false, '');
            }

            return;
        }

        if ($annex->showsAnswers()) {
            $pdf->AddPage();
            $this->renderSectionTitle($pdf, $this->trans('Metodología del análisis de materialidad', $locale));
            $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->buildTableHtml($this->materialityQuestionHeader($locale), $this->materialityQuestionRows($annex, $locale), ['18%', '52%', '15%', '15%']), true, false, true, false, '');
        }

        $pdf->AddPage();
        $this->renderSectionTitle($pdf, $this->trans('Resultado por categoría', $locale));

        if ($annex->isReopened()) {
            $this->setFont($pdf, 9.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
            $pdf->writeHTML($this->markdownToStyledHtml((string) $this->materialityAnnexStatement($data, $locale)), true, false, true, false, '');
        }

        $this->setFont($pdf, 8, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->writeHTML($this->buildMaterialityMatrixHtml($annex, $locale, ['7%', '27%', '12%', '10%', '14%', '16%', '14%']), true, false, true, false, '');

        if (!$data->scope3Excluded && [] !== $annex->justifications) {
            $pdf->AddPage();
            $this->renderSectionTitle($pdf, $this->trans('Justificación de cada exclusión', $locale));

            foreach ($annex->justifications as $justification) {
                $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
                $pdf->writeHTML(sprintf('<h4>%s</h4>', htmlspecialchars($this->materialityTopicLabel($justification->topic, $locale))), true, false, true, false, '');

                if ([] !== $justification->answers) {
                    $this->setFont($pdf, 8.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
                    $pdf->writeHTML($this->buildTableHtml($this->materialityAnswerHeader($locale), $this->materialityAnswerRows($justification, $locale), ['18%', '46%', '14%', '10%', '12%']), true, false, true, false, '');
                }

                $this->setFont($pdf, 9.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
                $pdf->writeHTML($this->markdownToStyledHtml($this->materialityJustificationText($justification, $locale)), true, false, true, false, '');
                $pdf->Ln(2);
            }
        }

        $pdf->Ln(4);
        $this->renderSectionTitle($pdf, $this->trans('Trazabilidad', $locale));
        $this->setFont($pdf, 9.5, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->writeHTML($this->markdownToStyledHtml($this->traceabilityMarkdown($annex, $locale)), true, false, true, false, '');
    }

    private function traceabilityMarkdown(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, Locale $locale): string
    {
        $assessment = $annex->assessment;
        $items = [];

        if (null !== $assessment) {
            $items[] = $this->trans('Identificador de la evaluación: {id}', $locale, ['{id}' => $assessment->assessmentId]);
            $items[] = $this->trans('Iniciada el {started}; completada el {completed} por {user}', $locale, [
                '{started}' => $assessment->startedAt->format('d/m/Y'),
                '{completed}' => $assessment->completedAt?->format('d/m/Y') ?? '---',
                '{user}' => $this->materialityPlain($assessment->completedByName ?? '---'),
            ]);
        }

        foreach ($annex->topics as $topic) {
            if (null === $topic->overriddenByName) {
                continue;
            }

            $items[] = $this->trans('Anulación de {category}: {user}, {date}, «{justification}»', $locale, [
                '{category}' => $this->materialityPlain($this->materialityTopicLabel($topic, $locale)),
                '{user}' => $this->materialityPlain($topic->overriddenByName),
                '{date}' => $topic->overriddenAt?->format('d/m/Y') ?? '---',
                '{justification}' => $this->materialityPlain($topic->overrideJustification ?? ''),
            ]);
        }

        if (true === $assessment?->questionSetChangedSince) {
            $items[] = $this->trans('El cuestionario del maestro ha cambiado desde la evaluación: las preguntas afectadas se marcan como modificadas o retiradas', $locale);
        }

        $items[] = null !== $assessment
            ? $this->trans('El historial completo de la evaluación {id} y de la huella está disponible para los Administradores en el Registro', $locale, ['{id}' => $assessment->assessmentId])
            : $this->trans('El historial completo de la huella está disponible para los Administradores en el Registro', $locale);

        return implode("\n", array_map(static fn(string $item): string => '- ' . $item, $items));
    }

    private function materialityMatrixHeaderCell(string $headerCell, string $width): string
    {
        return sprintf('<th style="background-color: %s; color: %s; text-align: left;%s">%s</th>', $this->pdfBranding()->darkHex(), $this->pdfBranding()->subtleHex(), $width, htmlspecialchars($headerCell));
    }
}
