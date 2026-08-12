<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Tcpdf;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateData;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificatePdfGenerator;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Qr\QrCodeGenerator;
use App\Shared\Infrastructure\Tcpdf\GenericGenerator;
use App\Shared\Infrastructure\Tcpdf\ReportTcpdf;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TcpdfContributionCertificatePdfGenerator implements ContributionCertificatePdfGenerator
{
    use GenericGenerator;

    private const float PAGE_HEIGHT = 210.0;

    private const float SIDE_BAR_WIDTH = 6.5;

    private const float LEFT = 19.5;
    private const float RIGHT = 281.0;

    private const float HEADER_RULE_Y = 28.0;
    private const float CODE_Y = 35.0;
    private const float TITLE_Y = 43.0;
    private const float TITLE_WIDTH = 150.0;
    private const float LIME_RULE_Y = 77.0;

    private const float BOX_TOP = 84.5;
    private const float BOX_WIDTH = 129.0;
    private const float BOX_PADDING = 8.0;
    private const float BOX_ROW_HEIGHT = 23.0;

    private const float DETAILS_LEFT = 159.0;
    private const float DETAILS_TOP = 84.0;
    private const float DETAILS_COLUMN_WIDTH = 61.0;
    private const float DETAILS_ROW_HEIGHT = 14.0;

    private const float VERIFICATION_MIN_Y = 150.0;
    private const float QR_SIZE = 20.0;

    private const float FOOTER_RULE_Y = 187.0;
    private const float SIGNATURE_LINE_Y = 194.0;

    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_MUTED_TEXT = [122, 132, 140];

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly QrCodeGenerator $qrCodeGenerator,
    ) {}

    public function generate(ContributionCertificateData $data, Locale $locale): string
    {
        $previousLocale = LocaleGetter::locale();
        LocaleGetter::setLocale($locale);

        try {
            $documentName = $this->documentName($data, $locale);

            $pdf = $this->createPDF($documentName);
            $pdf->AddPage('L');

            $this->renderChrome($pdf, $documentName);
            $this->renderHeading($pdf, $data, $documentName, $locale);
            $this->renderBeneficiaryBox($pdf, $data, $locale, $this->renderDetails($pdf, $data, $locale));
            $this->renderFooter($pdf, $data, $locale);

            $path = sys_get_temp_dir() . '/' . uniqid('contribution-certificate-', true) . '-' . $locale->value . '.pdf';
            $this->savePDF($pdf, $path);

            return $path;
        } finally {
            LocaleGetter::setLocale($previousLocale);
        }
    }

    private function documentName(ContributionCertificateData $data, Locale $locale): string
    {
        return $data->type->isCompensation()
            ? $this->trans('Certificado de Compensación de Carbono', $locale)
            : $this->trans('Certificado de Aportación Social', $locale);
    }

    private function renderChrome(ReportTcpdf $pdf, string $documentName): void
    {
        $pdf->Rect(0, 0, self::SIDE_BAR_WIDTH, self::PAGE_HEIGHT, 'F', [], self::COLOR_LIME_RGB);

        $logoPath = $this->getImagePath('logo-dark.png');

        if (is_file($logoPath)) {
            $pdf->Image($logoPath, self::LEFT, 12, 42, 14, '', '', '', false, 300);
        }

        $this->setFont($pdf, 8, self::FONT_STYLE_SEMIBOLD, self::COLOR_TEAL_RGB);
        $pdf->setFontSpacing(0.7);
        $pdf->setXY(self::RIGHT - 130, 16);
        $pdf->Cell(130, 5, mb_strtoupper($documentName), 0, 0, 'R');
        $pdf->setFontSpacing(0);

        $pdf->Line(self::LEFT, self::HEADER_RULE_Y, self::RIGHT, self::HEADER_RULE_Y, ['width' => 0.2, 'color' => self::COLOR_FAINT_LINE]);
    }

    private function renderHeading(ReportTcpdf $pdf, ContributionCertificateData $data, string $documentName, Locale $locale): void
    {
        $this->renderLabel($pdf, self::LEFT, self::CODE_Y, 160, $this->trans('Certificado nº', $locale) . ' ' . $data->code);

        $size = 30.0;
        $this->setFont($pdf, $size, self::FONT_STYLE_MEDIUM, self::COLOR_DARK_TEAL);

        while ($size > 18.0 && $pdf->getNumLines($documentName, self::TITLE_WIDTH) > 2) {
            $size -= 2.0;
            $this->setFont($pdf, $size, self::FONT_STYLE_MEDIUM, self::COLOR_DARK_TEAL);
        }

        $pdf->setXY(self::LEFT, self::TITLE_Y);
        $pdf->MultiCell(self::TITLE_WIDTH, $size * 0.42, $documentName, 0, 'L');

        $pdf->Line(self::LEFT, self::LIME_RULE_Y, self::LEFT + 26, self::LIME_RULE_Y, ['width' => 1.4, 'color' => self::COLOR_LIME_RGB]);
    }

    private function renderBeneficiaryBox(ReportTcpdf $pdf, ContributionCertificateData $data, Locale $locale, float $desiredBottom): void
    {
        $innerLeft = self::LEFT + self::BOX_PADDING;
        $innerWidth = self::BOX_WIDTH - (2 * self::BOX_PADDING);

        $subtitle = $this->beneficiarySubtitle($data, $locale);
        $rows = $this->contributionRows($data, $locale);

        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, self::COLOR_MUTED_TEXT);
        $subtitleLines = max(1, $pdf->getNumLines($subtitle, $innerWidth));

        $headHeight = 26.5 + (($subtitleLines - 1) * 4.5);
        $height = max(
            6.5 + $headHeight + (count($rows) * self::BOX_ROW_HEIGHT) + 3,
            $desiredBottom - self::BOX_TOP,
        );

        $pdf->Rect(self::LEFT, self::BOX_TOP, self::BOX_WIDTH, $height, 'F', [], self::COLOR_CREAM);

        $y = self::BOX_TOP + 6.5;

        $this->renderLabel($pdf, $innerLeft, $y, $innerWidth, $this->trans('Beneficiario', $locale));

        $this->fitFont($pdf, $data->beneficiaryName, $innerWidth, 18.0, 11.0, self::FONT_STYLE_BOLD, self::COLOR_DARK_TEAL);
        $pdf->setXY($innerLeft, $y + 4.5);
        $pdf->Cell($innerWidth, 8, $data->beneficiaryName, 0, 0, 'L');

        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, self::COLOR_MUTED_TEXT);
        $pdf->setXY($innerLeft, $y + 14.5);
        $pdf->MultiCell($innerWidth, 4.5, $subtitle, 0, 'L');

        $y += $headHeight;

        foreach ($rows as $row) {
            $pdf->Line($innerLeft, $y, $innerLeft + $innerWidth, $y, ['width' => 0.2, 'color' => self::COLOR_FAINT_LINE]);

            $this->renderLabel($pdf, $innerLeft, $y + 5, $innerWidth, $row[0]);

            if (null === $row[2]) {
                $this->fitFont($pdf, $row[1], $innerWidth, 16.0, 10.0, self::FONT_STYLE_BOLD, self::COLOR_DARK_TEAL);
                $pdf->setXY($innerLeft, $y + 10.5);
                $pdf->Cell($innerWidth, 8, $row[1], 0, 0, 'L');
            } else {
                $this->renderAmount($pdf, $innerLeft, $y + 10.5, $row[1], $row[2]);
            }

            $y += self::BOX_ROW_HEIGHT;
        }
    }

    private function beneficiarySubtitle(ContributionCertificateData $data, Locale $locale): string
    {
        $parts = [sprintf('%s · %s', $this->trans('País', $locale), $data->contributorCountry)];

        if (null !== $data->beneficiaryVatNumber && '' !== $data->beneficiaryVatNumber) {
            $parts[] = sprintf('%s · %s', $this->trans('NIF / NIF-IVA', $locale), $data->beneficiaryVatNumber);
        }

        if ($data->beneficiaryIsCompany) {
            $parts[] = sprintf('%s · %s', $this->trans('Persona de contacto', $locale), $data->contactName);
        }

        return implode('    ', $parts);
    }

    /** @return list<array{0: string, 1: string, 2: string|null}> */
    private function contributionRows(ContributionCertificateData $data, Locale $locale): array
    {
        $rows = [];

        $originLabel = $this->originLabel($data, $locale);

        if (null !== $originLabel && null !== $data->originName && '' !== $data->originName) {
            $rows[] = [$originLabel, $data->originName, null];
        }

        $rows[] = [
            $data->type->isCompensation()
                ? $this->trans('Aportación al proyecto', $locale)
                : $this->trans('Aportación recibida', $locale),
            number_format($data->contributionAmount, 2, ',', '.'),
            $data->currency,
        ];

        return $rows;
    }

    private function originLabel(ContributionCertificateData $data, Locale $locale): ?string
    {
        return match ($data->origin) {
            ProjectContributionOrigin::AttendeeSurveyResponse => $this->trans('Evento', $locale),
            ProjectContributionOrigin::PropertyTripCalculation => $this->trans('Alojamiento', $locale),
            null => null,
        };
    }

    private function renderDetails(ReportTcpdf $pdf, ContributionCertificateData $data, Locale $locale): float
    {
        $y = self::DETAILS_TOP;
        $columnWidth = self::DETAILS_COLUMN_WIDTH - 4;

        foreach (array_chunk($this->detailCells($data, $locale), 2) as $pair) {
            foreach ($pair as $index => $cell) {
                $x = self::DETAILS_LEFT + ($index * self::DETAILS_COLUMN_WIDTH);

                $this->renderLabel($pdf, $x, $y, $columnWidth, $cell[0]);

                $this->fitFont($pdf, $cell[1], $columnWidth, 14.0, 9.0, self::FONT_STYLE_MEDIUM, self::COLOR_DARK_TEAL);
                $pdf->setXY($x, $y + 4.5);
                $pdf->Cell($columnWidth, 6, $cell[1], 0, 0, 'L');
            }

            $y += self::DETAILS_ROW_HEIGHT;
        }

        $y = $this->renderSerials($pdf, $data, $locale, $y);

        return $this->renderVerification($pdf, $data, $locale, max($y + 2, self::VERIFICATION_MIN_Y)) ?? $y;
    }

    /** @return list<array{0: string, 1: string}> */
    private function detailCells(ContributionCertificateData $data, Locale $locale): array
    {
        $cells = [[$this->trans('Proyecto', $locale), $data->projectName]];

        if (null !== $data->projectLocation && '' !== $data->projectLocation) {
            $cells[] = [$this->trans('Ubicación', $locale), $data->projectLocation];
        }

        if ($data->type->isCompensation() && null !== $data->co2eqKg) {
            $cells[] = [
                $this->trans('Emisiones compensadas', $locale),
                sprintf('%s kg CO₂e', number_format($data->co2eqKg, 2, ',', '.')),
            ];
        }

        $cells[] = [$this->trans('Fecha de emisión', $locale), $data->issuedAt->format('d/m/Y')];
        $cells[] = [$this->trans('Entidad emisora', $locale), self::DOCUMENT_AUTHOR];

        if ($data->type->isCompensation() && null !== $data->projectRegistryId && '' !== $data->projectRegistryId) {
            $cells[] = [$this->trans('Registro oficial', $locale), $data->projectRegistryId];
        }

        return $cells;
    }

    private function renderSerials(ReportTcpdf $pdf, ContributionCertificateData $data, Locale $locale, float $y): float
    {
        if (!$data->type->isCompensation() || [] === $data->masterSerials) {
            return $y;
        }

        $width = self::RIGHT - self::DETAILS_LEFT;

        $this->renderLabel($pdf, self::DETAILS_LEFT, $y, $width, $this->trans('Números de serie maestros', $locale));

        $this->setFont($pdf, 12, self::FONT_STYLE_MEDIUM, self::COLOR_DARK_TEAL);
        $pdf->setXY(self::DETAILS_LEFT, $y + 4.5);
        $pdf->MultiCell($width, 5, implode(' · ', $data->masterSerials), 0, 'L');

        return $pdf->GetY() + 2;
    }

    private function renderVerification(ReportTcpdf $pdf, ContributionCertificateData $data, Locale $locale, float $y): ?float
    {
        if (null === $data->verificationUrl) {
            return null;
        }

        $binary = $this->qrPng($data->verificationUrl);

        if (null === $binary) {
            return null;
        }

        $pdf->Line(self::DETAILS_LEFT, $y, self::RIGHT, $y, ['width' => 0.2, 'color' => self::COLOR_FAINT_LINE]);

        $top = $y + 6;
        $textLeft = self::DETAILS_LEFT + self::QR_SIZE + 5;
        $textWidth = self::RIGHT - $textLeft;

        $pdf->Image('@' . $binary, self::DETAILS_LEFT, $top, self::QR_SIZE, self::QR_SIZE, 'PNG');

        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->setXY($textLeft, $top);
        $pdf->MultiCell(
            $textWidth,
            4.6,
            $this->trans('Escanea este código QR para verificar el certificado.', $locale) . ' '
                . $this->trans('La referencia mostrada debe coincidir con la de este documento:', $locale),
            0,
            'L',
        );

        $this->setFont($pdf, 9, self::FONT_STYLE_BOLD, self::COLOR_DARK_TEAL);
        $pdf->setX($textLeft);
        $pdf->Cell($textWidth, 4.6, $data->code, 0, 0, 'L');

        return $top + self::QR_SIZE;
    }

    private function renderFooter(ReportTcpdf $pdf, ContributionCertificateData $data, Locale $locale): void
    {
        $pdf->Line(self::LEFT, self::FOOTER_RULE_Y, self::RIGHT, self::FOOTER_RULE_Y, ['width' => 0.2, 'color' => self::COLOR_FAINT_LINE]);

        $notice = $data->type->isCompensation()
            ? $this->trans('Este certificado acredita la retirada de créditos de carbono verificados por los lotes indicados. Los créditos consumidos quedan trazados y no pueden volver a utilizarse ni contabilizarse por ninguna otra parte.', $locale)
            : $this->trans('Este documento acredita la recepción de la aportación económica indicada y su destino al proyecto descrito. No acredita la reducción, la retirada ni la compensación de emisiones de gases de efecto invernadero.', $locale);

        $this->setFont($pdf, 8.5, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->setXY(self::LEFT, self::FOOTER_RULE_Y + 4);
        $pdf->MultiCell(self::CONTENT_WIDTH, 4.2, $notice, 0, 'L');

        $pdf->Line(self::RIGHT - 55, self::SIGNATURE_LINE_Y, self::RIGHT, self::SIGNATURE_LINE_Y, ['width' => 0.4, 'color' => self::COLOR_DARK_TEAL]);

        $this->renderLabel($pdf, self::RIGHT - 55, self::SIGNATURE_LINE_Y + 2, 55, $this->trans('Firma del emisor', $locale), 'R');
    }

    private function renderLabel(ReportTcpdf $pdf, float $x, float $y, float $width, string $text, string $align = 'L'): void
    {
        $this->setFont($pdf, 7, self::FONT_STYLE_SEMIBOLD, self::COLOR_TEAL_RGB);
        $pdf->setFontSpacing(0.7);
        $pdf->setXY($x, $y);
        $pdf->Cell($width, 4, mb_strtoupper($text), 0, 0, $align);
        $pdf->setFontSpacing(0);
    }

    private function renderAmount(ReportTcpdf $pdf, float $x, float $y, string $value, string $unit): void
    {
        $this->setFont($pdf, 20, self::FONT_STYLE_BOLD, self::COLOR_DARK_TEAL);
        $valueWidth = $pdf->GetStringWidth($value);
        $pdf->setXY($x, $y);
        $pdf->Cell($valueWidth + 2, 9, $value, 0, 0, 'L');

        $this->setFont($pdf, 12, self::FONT_STYLE_REGULAR, self::COLOR_TEAL_RGB);
        $pdf->setXY($x + $valueWidth + 2, $y + 2);
        $pdf->Cell(40, 7, $unit, 0, 0, 'L');
    }

    /** @param string|array{0: int, 1: int, 2: int} $color */
    private function fitFont(ReportTcpdf $pdf, string $text, float $maxWidth, float $size, float $minSize, string $style, string|array $color): void
    {
        $this->setFont($pdf, $size, $style, $color);

        while ($size > $minSize && $pdf->GetStringWidth($text) > $maxWidth) {
            $size -= 0.5;
            $this->setFont($pdf, $size, $style, $color);
        }
    }

    private function qrPng(string $url): ?string
    {
        $dataUri = $this->qrCodeGenerator->generate($url)->path;

        $comma = strpos($dataUri, ',');

        if (false === $comma) {
            return null;
        }

        $binary = base64_decode(substr($dataUri, $comma + 1), true);

        return false !== $binary ? $binary : null;
    }

    private function trans(string $id, Locale $locale): string
    {
        return $this->translator->trans($id, [], 'messages', $locale->value);
    }
}
