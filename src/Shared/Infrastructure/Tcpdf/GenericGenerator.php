<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Tcpdf;

use League\CommonMark\GithubFlavoredMarkdownConverter;

trait GenericGenerator
{
    private const string FONT_STYLE_REGULAR = 'regular';
    private const string FONT_STYLE_MEDIUM = 'medium';
    private const string FONT_STYLE_SEMIBOLD = 'semibold';
    private const string FONT_STYLE_BOLD = 'bold';
    private const string FONT_STYLE_BOLDITALIC = 'bolditalic';
    private const string FONT_STYLE_BLACK = 'black';

    private const string COLOR_PRIMARY = 'primary';
    private const string COLOR_SECONDARY = 'secondary';
    private const string COLOR_NEUTRAL_100 = 'neutral_100';
    private const string COLOR_NEUTRAL_900 = 'neutral_900';

    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_DARK_TEAL = [31, 72, 88];
    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_DARK_TEAL_SHADE = [44, 90, 108];
    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_LIGHT_TEAL_SHADE = [75, 120, 141];
    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_LIME_RGB = [193, 209, 24];
    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_TEAL_RGB = [64, 115, 142];
    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_CREAM = [244, 239, 224];
    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_BODY_TEXT = [55, 65, 75];
    /** @var array{0: int, 1: int, 2: int} */
    private const array COLOR_FAINT_LINE = [220, 225, 230];

    private const string HEX_TEAL = '#40738E';
    private const string HEX_BODY = '#37414B';
    private const string HEX_DARK_TEAL = '#1F4858';
    private const string HEX_CREAM = '#F4EFE0';

    private const string DOCUMENT_AUTHOR = 'Bioscore Sustainability';

    private const float CONTENT_MARGIN_LEFT = 16;
    private const float CONTENT_MARGIN_TOP = 40;
    private const float CONTENT_MARGIN_RIGHT = 16;
    private const float CONTENT_MARGIN_BOTTOM = 24;
    private const float CONTENT_WIDTH = 178;

    private function createPDF(string $title): ReportTcpdf
    {
        $pdf = new ReportTcpdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(self::DOCUMENT_AUTHOR);
        $pdf->SetAuthor(self::DOCUMENT_AUTHOR);
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetAutoPageBreak(false, 0);

        return $pdf;
    }

    private function startContentPages(ReportTcpdf $pdf, string $documentName): void
    {
        $pdf->setPageChrome(fn() => $this->renderContentPageChrome($pdf, $documentName));
        $pdf->SetMargins(self::CONTENT_MARGIN_LEFT, self::CONTENT_MARGIN_TOP, self::CONTENT_MARGIN_RIGHT, true);
        $pdf->SetAutoPageBreak(true, self::CONTENT_MARGIN_BOTTOM);
    }

    /** @return array{0: int, 1: int, 2: int}|null */
    private function hex2rgb(string $hexColor): ?array
    {
        $hexColor = ltrim($hexColor, '#');

        $count = sscanf($hexColor, '%02x%02x%02x', $r, $g, $b);

        if (3 !== $count) {
            return null;
        }

        return [(int) $r, (int) $g, (int) $b];
    }

    private function getRootDir(): string
    {
        return __DIR__ . '/../../../../';
    }

    private function getFontPath(string $fontName): string
    {
        return $this->getRootDir() . 'assets/fonts/' . $fontName . '.ttf';
    }

    private function getImagePath(string $imageName): string
    {
        return $this->getRootDir() . 'assets/images/' . $imageName;
    }

    /** @param string|array{0: int, 1: int, 2: int} $color */
    private function setFont(\TCPDF $pdf, int|float $size, string $type = self::FONT_STYLE_REGULAR, string|array $color = self::COLOR_PRIMARY): void
    {
        $fontName = match ($type) {
            self::FONT_STYLE_BOLD => 'Rubik-Bold',
            self::FONT_STYLE_SEMIBOLD => 'Rubik-SemiBold',
            self::FONT_STYLE_BLACK => 'Rubik-Black',
            self::FONT_STYLE_REGULAR => 'Rubik-Regular',
            self::FONT_STYLE_BOLDITALIC => 'Rubik-BoldItalic',
            default => 'Rubik-Medium',
        };

        $font = \TCPDF_FONTS::addTTFfont($this->getFontPath($fontName), 'TrueTypeUnicode', '', 96);
        assert(is_string($font));

        $pdf->setFont($font, '', $size, '', false);
        $pdf->setFontSpacing(0);

        if (is_array($color)) {
            $pdf->setTextColor($color[0], $color[1], $color[2]);
        } else {
            match ($color) {
                self::COLOR_SECONDARY => $pdf->setTextColor(193, 209, 24),
                self::COLOR_NEUTRAL_100 => $pdf->setTextColor(255, 255, 255),
                self::COLOR_NEUTRAL_900 => $pdf->setTextColor(0, 0, 0),
                default => $pdf->setTextColor(64, 115, 142),
            };
        }
    }

    private function savePDF(\TCPDF $pdf, string $path): void
    {
        $pdf->Output($path, 'F');
    }

    /**
     * @param list<string>       $headerCells
     * @param list<list<string>> $rows
     * @param list<string>|null  $columnWidths
     */
    private function buildTableHtml(array $headerCells, array $rows, ?array $columnWidths = null): string
    {
        $html = '<table cellpadding="4" style="width: 100%; border-collapse: collapse;">';

        $html .= '<thead><tr>';
        foreach ($headerCells as $index => $headerCell) {
            $width = null !== $columnWidths ? sprintf(' width: %s;', $columnWidths[$index]) : '';
            $html .= sprintf('<th style="background-color: %s; color: %s; text-align: left;%s">%s</th>', self::HEX_DARK_TEAL, self::HEX_CREAM, $width, htmlspecialchars($headerCell));
        }
        $html .= '</tr></thead>';

        $html .= '<tbody>';
        foreach ($rows as $index => $row) {
            $backgroundColor = 0 === $index % 2 ? '#FFFFFF' : self::HEX_CREAM;
            $html .= sprintf('<tr style="background-color: %s;">', $backgroundColor);
            foreach ($row as $cellIndex => $cell) {
                $width = null !== $columnWidths ? sprintf(' width: %s;', $columnWidths[$cellIndex]) : '';
                $html .= sprintf('<td style="color: %s;%s">%s</td>', self::HEX_BODY, $width, htmlspecialchars($cell));
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    private function renderCover(\TCPDF $pdf, string $subtitle, string $title, string $date, string $footerLeft, string $documentName): void
    {
        $pdf->AddPage();

        $pdf->Rect(0, 0, 210, 297, 'F', [], self::COLOR_DARK_TEAL);

        $pdf->Circle(174, 26, 4.8, 0, 360, 'F', [], self::COLOR_LIME_RGB);
        $pdf->Circle(186, 40, 7.0, 0, 360, 'F', [], self::COLOR_LIME_RGB);
        $pdf->Circle(170, 50, 3.4, 0, 360, 'F', [], self::COLOR_LIME_RGB);

        $pdf->Circle(71, 237, 95, 0, 360, 'F', [], self::COLOR_DARK_TEAL_SHADE);
        $pdf->Circle(52, 230, 28, 0, 360, 'F', [], self::COLOR_LIGHT_TEAL_SHADE);
        $pdf->Circle(28, 271, 10, 0, 360, 'F', [], self::COLOR_LIGHT_TEAL_SHADE);

        $logoPath = $this->getImagePath('logo-white.png');
        if (is_file($logoPath)) {
            $pdf->Image($logoPath, 20, 20, 60, 20, '', '', '', false, 300);
        }

        $this->setFont($pdf, 8, self::FONT_STYLE_MEDIUM, self::COLOR_CREAM);
        $pdf->setFontSpacing(0.6);
        $pdf->setXY(20, 45);
        $pdf->Cell(0, 5, mb_strtoupper($subtitle), 0, 0, 'L');
        $pdf->setFontSpacing(0);

        $pdf->Line(18, 214, 28, 214, ['width' => 0.9, 'color' => self::COLOR_LIME_RGB]);

        $this->setFont($pdf, 9, self::FONT_STYLE_SEMIBOLD, self::COLOR_LIME_RGB);
        $pdf->setFontSpacing(0.8);
        $pdf->setXY(14, 206);
        $pdf->Cell(184, 5, mb_strtoupper($documentName), 0, 0, 'L');
        $pdf->setFontSpacing(0);

        $titleFontSize = 48;
        $this->setFont($pdf, $titleFontSize, self::FONT_STYLE_MEDIUM, self::COLOR_CREAM);
        while ($titleFontSize > 24 && $pdf->getNumLines($title, 184) > 2) {
            $titleFontSize -= 4;
            $this->setFont($pdf, $titleFontSize, self::FONT_STYLE_MEDIUM, self::COLOR_CREAM);
        }
        $pdf->setXY(14, 217);
        $pdf->MultiCell(184, $titleFontSize * 0.375, $title, 0, 'L');

        $this->setFont($pdf, 18, self::FONT_STYLE_MEDIUM, self::COLOR_LIME_RGB);
        $pdf->setX(14);
        $pdf->Cell(184, 10, $date, 0, 0, 'L');

        $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_CREAM);
        $pdf->setFontSpacing(0.6);
        $pdf->setXY(14, 282);
        $pdf->Cell(90, 5, $footerLeft, 0, 0, 'L');
        $pdf->setXY(104, 282);
        $pdf->Cell(94, 5, mb_strtoupper($documentName), 0, 0, 'R');
        $pdf->setFontSpacing(0);
    }

    private function renderContentPageChrome(\TCPDF $pdf, string $documentName): void
    {
        $pdf->Rect(0, 36, 2.4, 220, 'F', [], self::COLOR_LIME_RGB);

        $logoPath = $this->getImagePath('logo-dark.png');
        if (is_file($logoPath)) {
            $pdf->Image($logoPath, 14, 12, 42, 14, '', '', '', false, 300);
        }

        $this->setFont($pdf, 8, self::FONT_STYLE_SEMIBOLD, self::COLOR_TEAL_RGB);
        $pdf->setFontSpacing(0.6);
        $pdf->setXY(110, 16);
        $pdf->Cell(86, 5, mb_strtoupper($documentName), 0, 0, 'R');
        $pdf->setFontSpacing(0);

        $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_TEAL_RGB);
        $pdf->setXY(14, 282);
        $pdf->Cell(120, 5, mb_strtoupper($documentName) . ' · BIOSCORE SUSTAINABILITY', 0, 0, 'L');

        $pdf->Circle(174, 284.5, 1.2, 0, 360, 'F', [], self::COLOR_LIME_RGB);
        $pdf->setXY(178, 282);
        $pdf->Cell(18, 5, $pdf->getAliasNumPage() . ' / ' . $pdf->getAliasNbPages(), 0, 0, 'L');
    }

    private function renderSectionTitle(\TCPDF $pdf, string $title): void
    {
        $pdf->setXY(16, 40);
        $this->setFont($pdf, 26, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);

        $maxWidth = 170.0;

        if ($pdf->GetStringWidth($title) > $maxWidth) {
            $words = explode(' ', $title);
            $line1 = '';
            $line2 = '';
            $accumulatedWidth = 0.0;
            $totalWidth = $pdf->GetStringWidth($title);

            foreach ($words as $word) {
                $wordWidth = $pdf->GetStringWidth($word . ' ');

                if ($accumulatedWidth + $wordWidth <= $totalWidth / 2 || '' === $line1) {
                    $line1 .= $word . ' ';
                    $accumulatedWidth += $wordWidth;
                } else {
                    $line2 .= $word . ' ';
                }
            }

            $title = trim($line1) . "\n" . trim($line2);
        }

        $pdf->MultiCell(170, 12, $title, 0, 'L');

        $y = $pdf->GetY() + 2;
        $pdf->Line(16, $y, 44, $y, ['width' => 0.9, 'color' => self::COLOR_LIME_RGB]);
        $pdf->Line(44, $y, 192, $y, ['width' => 0.2, 'color' => self::COLOR_FAINT_LINE]);

        $pdf->setY($y + 6);
    }

    private function markdownToStyledHtml(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter();
        $html = (string) $converter->convert($markdown);

        $html = preg_replace_callback(
            '/<h1>(.*?)<\/h1>/s',
            fn(array $matches) => sprintf('<h1><font color="%s">%s</font></h1>', self::HEX_TEAL, $matches[1]),
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/<h2>(.*?)<\/h2>/s',
            fn(array $matches) => sprintf('<h2>&middot; <font color="%s">%s</font></h2>', self::HEX_TEAL, $matches[1]),
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/<h3>(.*?)<\/h3>/s',
            fn(array $matches) => sprintf('<h3><font color="%s">%s</font></h3>', self::HEX_TEAL, mb_strtoupper($matches[1])),
            $html,
        ) ?? $html;

        $html = str_replace('<strong>', sprintf('<strong><font color="%s">', self::HEX_TEAL), $html);
        $html = str_replace('</strong>', '</font></strong>', $html);

        $html = preg_replace('/<li><p>(.*?)<\/p><\/li>/s', '<li>$1</li>', $html) ?? $html;
        $html = str_replace('<li>', sprintf('<li><font color="%s">', self::HEX_BODY), $html);
        $html = str_replace('</li>', '</font></li>', $html);

        $html = str_replace('<p>', sprintf('<p><font color="%s">', self::HEX_BODY), $html);

        return str_replace('</p>', '</font></p>', $html);
    }
}
