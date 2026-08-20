<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\Writer;

trait BrandedPdfBuilderTrait
{
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_LIME_RGB = [193, 209, 24];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_TEAL_RGB = [64, 115, 142];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_BODY_TEXT = [55, 65, 75];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_FAINT_LINE = [220, 225, 230];

    private const HEX_DARK_TEAL = '#1F4858';
    private const HEX_CREAM = '#F4EFE0';
    private const HEX_BODY = '#37414B';
    private const HEX_FAINT_LINE = '#DCE1E6';

    private const FONT_STYLE_REGULAR = 'regular';
    private const FONT_STYLE_MEDIUM = 'medium';
    private const FONT_STYLE_SEMIBOLD = 'semi-bold';

    private const PAGE_WIDTH = 297;
    private const PAGE_HEIGHT = 210;
    private const MARGIN_LEFT = 14;
    private const MARGIN_RIGHT = 14;
    private const MARGIN_TOP = 32;
    private const MARGIN_BOTTOM = 22;

    private function createBrandedPDF(string $title): \TCPDF
    {
        $pdf = new \TCPDF('L');
        $pdf->setCreator('Bioscore Sustainability');
        $pdf->setAuthor('Bioscore Sustainability');
        $pdf->setTitle($title);
        $pdf->setMargins(self::MARGIN_LEFT, self::MARGIN_TOP, self::MARGIN_RIGHT, true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setAutoPageBreak(true, self::MARGIN_BOTTOM);

        return $pdf;
    }

    private function renderBrandedTitle(\TCPDF $pdf, string $title, string $dateFormatted): void
    {
        $pdf->setXY(16, self::MARGIN_TOP);
        $this->setBrandedFont($pdf, 26, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->Cell(180, 12, $title, 0, 0, 'L');

        $this->setBrandedFont($pdf, 12, self::FONT_STYLE_REGULAR, self::COLOR_LIME_RGB);
        $pdf->Cell(self::PAGE_WIDTH - self::MARGIN_RIGHT - $pdf->getX() - 2, 12, $dateFormatted, 0, 0, 'R');

        $y = self::MARGIN_TOP + 14;
        $pdf->SetDrawColor(self::COLOR_LIME_RGB[0], self::COLOR_LIME_RGB[1], self::COLOR_LIME_RGB[2]);
        $pdf->SetLineWidth(0.9);
        $pdf->Line(16, $y, 44, $y);
        $pdf->SetDrawColor(self::COLOR_FAINT_LINE[0], self::COLOR_FAINT_LINE[1], self::COLOR_FAINT_LINE[2]);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(44, $y, self::PAGE_WIDTH - self::MARGIN_RIGHT - 2, $y);
        $pdf->setY($y + 6);
    }

    /**
     * @param array<int, scalar|null>             $headerCells
     * @param array<int, array<int, scalar|null>> $rows
     */
    private function buildBrandedTableHtml(array $headerCells, array $rows): string
    {
        $columnWidth = number_format(100 / max(1, count($headerCells)), 2) . '%';

        $html = '<table cellpadding="5" cellspacing="0"><thead><tr style="background-color:' . self::HEX_DARK_TEAL . ';color:' . self::HEX_CREAM . ';">';

        foreach ($headerCells as $header) {
            $html .= '<td width="' . $columnWidth . '"><b>' . htmlspecialchars(mb_strtoupper((string) $header)) . '</b></td>';
        }

        $html .= '</tr></thead>';

        foreach ($rows as $index => $row) {
            $backgroundColor = 0 === $index % 2 ? '#FFFFFF' : self::HEX_CREAM;

            $html .= '<tr style="background-color:' . $backgroundColor . ';color:' . self::HEX_BODY . ';border-bottom:0.2mm solid ' . self::HEX_FAINT_LINE . ';">';

            foreach ($row as $cell) {
                $html .= '<td width="' . $columnWidth . '">' . nl2br(htmlspecialchars((string) $cell)) . '</td>';
            }

            $html .= '</tr>';
        }

        return $html . '</table>';
    }

    private function renderBrandedChromeOnEveryPage(\TCPDF $pdf, string $titleUpper): void
    {
        $pageCount = $pdf->getNumPages();

        for ($page = 1; $page <= $pageCount; ++$page) {
            $pdf->setPage($page);

            // setPage() restores the per-page break state, so it must be disabled after every call
            $pdf->setAutoPageBreak(false, 0);

            $pdf->Rect(0, 26, 2.4, self::PAGE_HEIGHT - 56, 'F', [], self::COLOR_LIME_RGB);

            $pdf->Image($this->getBrandedImagePath('logo-dark.png'), self::MARGIN_LEFT, 10, 42, 14, '', '', '', false, 300, '', false, false, 0);

            $pdf->setXY(self::PAGE_WIDTH - self::MARGIN_RIGHT - 120, 14);
            $this->setBrandedFont($pdf, 8, self::FONT_STYLE_SEMIBOLD, self::COLOR_TEAL_RGB);
            $pdf->setFontSpacing(0.6);
            $pdf->Cell(120, 5, $titleUpper, 0, 0, 'R');
            $pdf->setFontSpacing(0);

            $pdf->setXY(self::MARGIN_LEFT, self::PAGE_HEIGHT - 15);
            $this->setBrandedFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_TEAL_RGB);
            $pdf->setFontSpacing(0.6);
            $pdf->Cell(140, 5, $titleUpper . ' · BIOSCORE SUSTAINABILITY', 0, 0, 'L');

            $pdf->Circle(self::PAGE_WIDTH - 36, self::PAGE_HEIGHT - 12.5, 1.2, 0, 360, 'F', [], self::COLOR_LIME_RGB);
            $pdf->setXY(self::PAGE_WIDTH - 33, self::PAGE_HEIGHT - 15);
            $this->setBrandedFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_TEAL_RGB);
            $pdf->Cell(20, 5, $page . ' / ' . $pageCount, 0, 0, 'L');
            $pdf->setFontSpacing(0);
        }

        $pdf->setPage($pageCount);
    }

    /** @param array{0: int, 1: int, 2: int} $color */
    private function setBrandedFont(\TCPDF $pdf, int|float $size, string $type, array $color): void
    {
        $fontName = match ($type) {
            self::FONT_STYLE_SEMIBOLD => 'Rubik/Rubik-SemiBold',
            self::FONT_STYLE_MEDIUM => 'Rubik/Rubik-Medium',
            default => 'Rubik/Rubik-Regular',
        };

        $font = \TCPDF_FONTS::addTTFfont($this->getBrandedFontPath($fontName), 'TrueTypeUnicode', '', 96);

        assert(is_string($font));

        $pdf->setFont($font, '', $size, '', false);
        $pdf->setFontSpacing(0);
        $pdf->setTextColor($color[0], $color[1], $color[2]);
    }

    private function getBrandedRootDir(): string
    {
        return __DIR__ . '/../../../../../';
    }

    private function getBrandedFontPath(string $fontName): string
    {
        return sprintf('%sassets/fonts/%s.ttf', $this->getBrandedRootDir(), $fontName);
    }

    private function getBrandedImagePath(string $filename): string
    {
        return sprintf('%sassets/images/%s', $this->getBrandedRootDir(), $filename);
    }
}
