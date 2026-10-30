<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateData;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificatePdfGenerator;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Qr\QrCodeGenerator;
use App\Shared\Infrastructure\Tcpdf\GenericGenerator;
use App\Shared\Infrastructure\Tcpdf\ReportTcpdf;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TcpdfPropertyCarbonFootprintCertificatePdfGenerator implements PropertyCarbonFootprintCertificatePdfGenerator
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
    private const float YEAR_WIDTH = 70.0;
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

    private const float FOOTER_RULE_Y = 184.0;
    private const float SIGNATURE_LINE_Y = 194.0;
    private const float SIGNATURE_WIDTH = 55.0;
    private const float SIGNATURE_IMAGE_WIDTH = 30.7;
    private const float SIGNATURE_IMAGE_HEIGHT = 15.0;

    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_MUTED_TEXT = [122, 132, 140];

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly QrCodeGenerator $qrCodeGenerator,
    ) {}

    public function generate(PropertyCarbonFootprintCertificateData $data, Locale $locale, PdfBranding $branding): string
    {
        $previousLocale = LocaleGetter::locale();
        LocaleGetter::setLocale($locale);

        try {
            $documentName = $this->trans('Certificado de huella', $locale);

            $pdf = $this->createPDF($documentName, $branding);
            $pdf->AddPage('L');

            $this->renderChrome($pdf, $documentName);
            $this->renderHeading($pdf, $data, $locale);
            $this->renderPropertyBox($pdf, $data, $locale, $this->renderDetails($pdf, $data, $locale));
            $this->renderFooter($pdf, $locale);

            $path = sys_get_temp_dir() . '/' . uniqid('footprint-certificate-', true) . '-' . $locale->value . '.pdf';
            $this->savePDF($pdf, $path);

            return $path;
        } finally {
            LocaleGetter::setLocale($previousLocale);
        }
    }

    private function renderChrome(ReportTcpdf $pdf, string $documentName): void
    {
        $pdf->Rect(0, 0, self::SIDE_BAR_WIDTH, self::PAGE_HEIGHT, 'F', [], $this->pdfBranding()->secondary);

        $logoPath = $this->logoPath();

        if (is_file($logoPath)) {
            $pdf->Image(file: $logoPath, x: self::LEFT, y: 12, w: 45, h: 14, resize: false, dpi: 300, fitbox: 'LM');
        }

        $this->setFont($pdf, 8, self::FONT_STYLE_SEMIBOLD, $this->pdfBranding()->primary);
        $pdf->setFontSpacing(0.7);
        $pdf->setXY(self::RIGHT - 130, 16);
        $pdf->Cell(130, 5, mb_strtoupper($documentName), 0, 0, 'R');
        $pdf->setFontSpacing(0);

        $pdf->Line(self::LEFT, self::HEADER_RULE_Y, self::RIGHT, self::HEADER_RULE_Y, ['width' => 0.2, 'color' => $this->pdfBranding()->faintLine]);
    }

    private function renderHeading(ReportTcpdf $pdf, PropertyCarbonFootprintCertificateData $data, Locale $locale): void
    {
        $this->renderLabel($pdf, self::LEFT, self::CODE_Y, 160, $this->trans('Certificado nº', $locale) . ' ' . $data->code);

        $title = $this->trans('Certificado de huella de carbono', $locale);

        $size = 30.0;
        $this->setFont($pdf, $size, self::FONT_STYLE_MEDIUM, $this->pdfBranding()->dark);

        while ($size > 18.0 && $pdf->getNumLines($title, self::TITLE_WIDTH) > 2) {
            $size -= 2.0;
            $this->setFont($pdf, $size, self::FONT_STYLE_MEDIUM, $this->pdfBranding()->dark);
        }

        $pdf->setXY(self::LEFT, self::TITLE_Y);
        $pdf->MultiCell(self::TITLE_WIDTH, $size * 0.42, $title, 0, 'L');

        $this->renderLabel($pdf, self::RIGHT - self::YEAR_WIDTH, self::CODE_Y, self::YEAR_WIDTH, $this->trans('Año de la huella', $locale), 'R');

        $this->setFont($pdf, 40, self::FONT_STYLE_BOLD, $this->pdfBranding()->primary);
        $pdf->setXY(self::RIGHT - self::YEAR_WIDTH, self::TITLE_Y - 2);
        $pdf->Cell(self::YEAR_WIDTH, 17, (string) $data->year, 0, 0, 'R');

        $pdf->Line(self::LEFT, self::LIME_RULE_Y, self::LEFT + 26, self::LIME_RULE_Y, ['width' => 1.4, 'color' => $this->pdfBranding()->secondary]);
    }

    private function renderPropertyBox(ReportTcpdf $pdf, PropertyCarbonFootprintCertificateData $data, Locale $locale, float $desiredBottom): void
    {
        $innerLeft = self::LEFT + self::BOX_PADDING;
        $innerWidth = self::BOX_WIDTH - (2 * self::BOX_PADDING);

        $subtitle = $this->propertySubtitle($data, $locale);
        $rows = $this->footprintRows($data, $locale);

        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, self::COLOR_MUTED_TEXT);
        $subtitleLines = max(1, $pdf->getNumLines($subtitle, $innerWidth));

        $headHeight = 26.5 + (($subtitleLines - 1) * 4.5);
        $height = max(
            6.5 + $headHeight + (count($rows) * self::BOX_ROW_HEIGHT) + 3,
            $desiredBottom - self::BOX_TOP,
        );

        $pdf->Rect(self::LEFT, self::BOX_TOP, self::BOX_WIDTH, $height, 'F', [], $this->pdfBranding()->subtle);

        $y = self::BOX_TOP + 6.5;

        $this->renderLabel($pdf, $innerLeft, $y, $innerWidth, $this->trans('Propiedad', $locale));

        $this->fitFont($pdf, $data->propertyName, $innerWidth, 18.0, 8.0, self::FONT_STYLE_BOLD, $this->pdfBranding()->dark);
        $pdf->setXY($innerLeft, $y + 4.5);
        $pdf->Cell($innerWidth, 8, $data->propertyName, 0, 0, 'L');

        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, self::COLOR_MUTED_TEXT);
        $pdf->setXY($innerLeft, $y + 14.5);
        $pdf->MultiCell($innerWidth, 4.5, $subtitle, 0, 'L');

        $y += $headHeight;

        foreach ($rows as $row) {
            $pdf->Line($innerLeft, $y, $innerLeft + $innerWidth, $y, ['width' => 0.2, 'color' => $this->pdfBranding()->faintLine]);

            $this->renderLabel($pdf, $innerLeft, $y + 5, $innerWidth, $row[0]);
            $this->renderAmount($pdf, $innerLeft, $y + 10.5, $innerWidth, $row[1], $row[2]);

            $y += self::BOX_ROW_HEIGHT;
        }
    }

    private function propertySubtitle(PropertyCarbonFootprintCertificateData $data, Locale $locale): string
    {
        $lines = [];

        if (null !== $data->propertyAddress && '' !== $data->propertyAddress) {
            $lines[] = $data->propertyAddress;
        }

        $lines[] = sprintf('%s · %s', $this->trans('Organización', $locale), $data->accountName);

        return implode("\n", $lines);
    }

    /** @return list<array{0: string, 1: string, 2: string}> */
    private function footprintRows(PropertyCarbonFootprintCertificateData $data, Locale $locale): array
    {
        return [
            [
                $this->trans('Huella por estancia', $locale),
                number_format($data->co2eqKgPerStay, 2, ',', '.'),
                $this->trans('kg CO₂eq / estancia', $locale),
            ],
            [
                $this->trans('Estancias del año', $locale),
                number_format($data->stays, 0, ',', '.'),
                $this->trans('pernoctaciones', $locale),
            ],
        ];
    }

    private function renderDetails(ReportTcpdf $pdf, PropertyCarbonFootprintCertificateData $data, Locale $locale): float
    {
        $y = self::DETAILS_TOP;
        $columnWidth = self::DETAILS_COLUMN_WIDTH - 4;

        foreach (array_chunk($this->detailCells($data, $locale), 2) as $pair) {
            foreach ($pair as $index => $cell) {
                $x = self::DETAILS_LEFT + ($index * self::DETAILS_COLUMN_WIDTH);

                $this->renderLabel($pdf, $x, $y, $columnWidth, $cell[0]);

                $this->fitFont($pdf, $cell[1], $columnWidth, 14.0, 9.0, self::FONT_STYLE_MEDIUM, $this->pdfBranding()->dark);
                $pdf->setXY($x, $y + 4.5);
                $pdf->Cell($columnWidth, 6, $cell[1], 0, 0, 'L');
            }

            $y += self::DETAILS_ROW_HEIGHT;
        }

        return $this->renderVerification($pdf, $data, $locale, max($y + 2, self::VERIFICATION_MIN_Y)) ?? $y;
    }

    /** @return list<array{0: string, 1: string}> */
    private function detailCells(PropertyCarbonFootprintCertificateData $data, Locale $locale): array
    {
        return [
            [$this->trans('Emisiones totales', $locale), $this->emissions($data->totalCo2eqKg, $locale)],
            [$this->trans('Alcance 1', $locale), $this->emissions($data->scope1Co2eqKg, $locale)],
            [$this->trans('Alcance 2', $locale), $this->emissions($data->scope2Co2eqKg, $locale)],
            [
                $this->trans('Alcance 3', $locale),
                null !== $data->scope3Co2eqKg
                    ? $this->emissions($data->scope3Co2eqKg, $locale)
                    : $this->trans('Excluido del cálculo', $locale),
            ],
            [$this->trans('Fecha de emisión', $locale), $data->issuedAt->format('d/m/Y')],
            [$this->trans('Entidad emisora', $locale), $this->pdfBranding()->documentAuthor],
        ];
    }

    private function emissions(float $co2eqKg, Locale $locale): string
    {
        return sprintf('%s %s', number_format($co2eqKg, 2, ',', '.'), $this->trans('kg CO₂eq', $locale));
    }

    private function renderVerification(ReportTcpdf $pdf, PropertyCarbonFootprintCertificateData $data, Locale $locale, float $y): ?float
    {
        if (null === $data->verificationUrl) {
            return null;
        }

        $binary = $this->qrPng($data->verificationUrl);

        if (null === $binary) {
            return null;
        }

        $pdf->Line(self::DETAILS_LEFT, $y, self::RIGHT, $y, ['width' => 0.2, 'color' => $this->pdfBranding()->faintLine]);

        $top = $y + 6;
        $textLeft = self::DETAILS_LEFT + self::QR_SIZE + 5;
        $textWidth = self::RIGHT - $textLeft;

        $pdf->Image('@' . $binary, self::DETAILS_LEFT, $top, self::QR_SIZE, self::QR_SIZE, 'PNG');

        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->setXY($textLeft, $top);
        $pdf->MultiCell(
            $textWidth,
            4.6,
            $this->trans('Escanea este código QR para verificar el certificado.', $locale) . ' '
                . $this->trans('La referencia mostrada debe coincidir con la de este documento:', $locale),
            0,
            'L',
        );

        $this->setFont($pdf, 9, self::FONT_STYLE_BOLD, $this->pdfBranding()->dark);
        $pdf->setX($textLeft);
        $pdf->Cell($textWidth, 4.6, $data->code, 0, 0, 'L');

        return $top + self::QR_SIZE;
    }

    private function renderFooter(ReportTcpdf $pdf, Locale $locale): void
    {
        $noticeWidth = self::RIGHT - self::SIGNATURE_WIDTH - 5 - self::LEFT;

        $pdf->Line(self::LEFT, self::FOOTER_RULE_Y, self::LEFT + $noticeWidth, self::FOOTER_RULE_Y, ['width' => 0.2, 'color' => $this->pdfBranding()->faintLine]);

        $notice = $this->trans('Este certificado acredita la huella de carbono de la propiedad para el año indicado, calculada en esta plataforma conforme a los alcances 1, 2 y 3 del GHG Protocol (método de mercado). La cifra por estancia resulta de dividir las emisiones totales entre las pernoctaciones declaradas por la organización. Vale como huella de ese año mientras no sea revocado; su vigencia puede comprobarse con el código QR.', $locale);

        $this->setFont($pdf, 8, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);
        $pdf->setXY(self::LEFT, self::FOOTER_RULE_Y + 3);
        $pdf->MultiCell($noticeWidth, 4, $notice, 0, 'L');

        $this->renderSignature($pdf);

        $pdf->Line(self::RIGHT - self::SIGNATURE_WIDTH, self::SIGNATURE_LINE_Y, self::RIGHT, self::SIGNATURE_LINE_Y, ['width' => 0.4, 'color' => $this->pdfBranding()->dark]);

        $this->renderLabel($pdf, self::RIGHT - self::SIGNATURE_WIDTH, self::SIGNATURE_LINE_Y + 2, self::SIGNATURE_WIDTH, $this->trans('Firma del emisor', $locale), 'R');
    }

    private function renderSignature(ReportTcpdf $pdf): void
    {
        $path = $this->getImagePath('emitter-signature.jpg');

        if (!is_file($path)) {
            return;
        }

        $pdf->Image(
            $path,
            self::RIGHT - (self::SIGNATURE_WIDTH + self::SIGNATURE_IMAGE_WIDTH) / 2,
            self::SIGNATURE_LINE_Y - self::SIGNATURE_IMAGE_HEIGHT - 0.5,
            self::SIGNATURE_IMAGE_WIDTH,
            self::SIGNATURE_IMAGE_HEIGHT,
            '',
            '',
            '',
            false,
            300,
        );
    }

    private function renderLabel(ReportTcpdf $pdf, float $x, float $y, float $width, string $text, string $align = 'L'): void
    {
        $this->setFont($pdf, 7, self::FONT_STYLE_SEMIBOLD, $this->pdfBranding()->primary);
        $pdf->setFontSpacing(0.7);
        $pdf->setXY($x, $y);
        $pdf->Cell($width, 4, mb_strtoupper($text), 0, 0, $align);
        $pdf->setFontSpacing(0);
    }

    private function renderAmount(ReportTcpdf $pdf, float $x, float $y, float $width, string $value, string $unit): void
    {
        $this->setFont($pdf, 20, self::FONT_STYLE_BOLD, $this->pdfBranding()->dark);
        $valueWidth = $pdf->GetStringWidth($value);
        $pdf->setXY($x, $y);
        $pdf->Cell($valueWidth + 2, 9, $value, 0, 0, 'L');

        $unitWidth = $width - $valueWidth - 2;

        $this->fitFont($pdf, $unit, $unitWidth, 12.0, 8.0, self::FONT_STYLE_REGULAR, $this->pdfBranding()->primary);
        $pdf->setXY($x + $valueWidth + 2, $y + 2);
        $pdf->Cell($unitWidth, 7, $unit, 0, 0, 'L');
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
