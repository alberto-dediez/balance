<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Tcpdf;

use App\Shared\Domain\Model\Branding\PdfBranding;
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


    private const float CONTENT_MARGIN_LEFT = 16;
    private const float CONTENT_MARGIN_TOP = 40;
    private const float CONTENT_MARGIN_RIGHT = 16;
    private const float CONTENT_MARGIN_BOTTOM = 24;
    private const float CONTENT_WIDTH = 178;

    private ?PdfBranding $pdfBranding = null;

    /** @var array<string, string> */
    private static array $registeredFonts = [];

    private function pdfBranding(): PdfBranding
    {
        return $this->pdfBranding ??= PdfBranding::bioscore();
    }

    private function createPDF(string $title, ?PdfBranding $branding = null): ReportTcpdf
    {
        $this->pdfBranding = $branding ?? PdfBranding::bioscore();

        $pdf = new ReportTcpdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($this->pdfBranding()->documentAuthor);
        $pdf->SetAuthor($this->pdfBranding()->documentAuthor);
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

    private function getFontPath(string $weight): string
    {
        $family = $this->pdfBranding()->fontFamily->value;

        $path = sprintf('%sassets/fonts/%s/%s-%s.ttf', $this->getRootDir(), $family, $family, $weight);

        if (is_file($path)) {
            return $path;
        }

        return sprintf('%sassets/fonts/Rubik/Rubik-%s.ttf', $this->getRootDir(), $weight);
    }

    private function getImagePath(string $imageName): string
    {
        return $this->getRootDir() . 'assets/images/' . $imageName;
    }

    /** @param string|array{0: int, 1: int, 2: int} $color */
    private function setFont(\TCPDF $pdf, int|float $size, string $type = self::FONT_STYLE_REGULAR, string|array $color = self::COLOR_PRIMARY): void
    {
        $weight = match ($type) {
            self::FONT_STYLE_BOLD => 'Bold',
            self::FONT_STYLE_SEMIBOLD => 'SemiBold',
            self::FONT_STYLE_BLACK => 'Black',
            self::FONT_STYLE_REGULAR => 'Regular',
            self::FONT_STYLE_BOLDITALIC => 'BoldItalic',
            default => 'Medium',
        };

        $font = $this->registerFont($this->getFontPath($weight));

        $pdf->setFont($font, '', $size, '', false);
        $pdf->setFontSpacing(0);

        if (is_array($color)) {
            $pdf->setTextColor($color[0], $color[1], $color[2]);
        } else {
            match ($color) {
                self::COLOR_SECONDARY => $pdf->setTextColor(...$this->pdfBranding()->secondary),
                self::COLOR_NEUTRAL_100 => $pdf->setTextColor(255, 255, 255),
                self::COLOR_NEUTRAL_900 => $pdf->setTextColor(0, 0, 0),
                default => $pdf->setTextColor(...$this->pdfBranding()->primary),
            };
        }
    }

    private function registerFont(string $path): string
    {
        if (isset(self::$registeredFonts[$path])) {
            return self::$registeredFonts[$path];
        }

        $font = \TCPDF_FONTS::addTTFfont($path, 'TrueTypeUnicode', '', 96);
        assert(is_string($font));

        return self::$registeredFonts[$path] = $font;
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
            $html .= sprintf('<th style="background-color: %s; color: %s; text-align: left;%s">%s</th>', $this->pdfBranding()->darkHex(), $this->pdfBranding()->subtleHex(), $width, htmlspecialchars($headerCell));
        }
        $html .= '</tr></thead>';

        $html .= '<tbody>';
        foreach ($rows as $index => $row) {
            $backgroundColor = 0 === $index % 2 ? '#FFFFFF' : $this->pdfBranding()->subtleHex();
            $html .= sprintf('<tr style="background-color: %s;">', $backgroundColor);
            foreach ($row as $cellIndex => $cell) {
                $width = null !== $columnWidths ? sprintf(' width: %s;', $columnWidths[$cellIndex]) : '';
                $html .= sprintf('<td style="color: %s;%s">%s</td>', $this->pdfBranding()->bodyTextHex(), $width, htmlspecialchars($cell));
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    private function renderCover(\TCPDF $pdf, string $subtitle, string $title, string $date, string $footerLeft, string $documentName): void
    {
        $pdf->AddPage();

        $pdf->Rect(0, 0, 210, 297, 'F', [], $this->pdfBranding()->dark);

        $pdf->Circle(174, 26, 4.8, 0, 360, 'F', [], $this->pdfBranding()->secondary);
        $pdf->Circle(186, 40, 7.0, 0, 360, 'F', [], $this->pdfBranding()->secondary);
        $pdf->Circle(170, 50, 3.4, 0, 360, 'F', [], $this->pdfBranding()->secondary);

        $pdf->Circle(71, 237, 95, 0, 360, 'F', [], $this->pdfBranding()->darkShade);
        $pdf->Circle(52, 230, 28, 0, 360, 'F', [], $this->pdfBranding()->lightShade);
        $pdf->Circle(28, 271, 10, 0, 360, 'F', [], $this->pdfBranding()->lightShade);

        $logoPath = $this->getImagePath('logo-white.png');
        if (is_file($logoPath)) {
            $pdf->Image($logoPath, 20, 20, 60, 20, '', '', '', false, 300);
        }

        $this->setFont($pdf, 8, self::FONT_STYLE_MEDIUM, $this->pdfBranding()->subtle);
        $pdf->setFontSpacing(0.6);
        $pdf->setXY(20, 45);
        $pdf->Cell(0, 5, mb_strtoupper($subtitle), 0, 0, 'L');
        $pdf->setFontSpacing(0);

        $pdf->Line(18, 214, 28, 214, ['width' => 0.9, 'color' => $this->pdfBranding()->secondary]);

        $this->setFont($pdf, 9, self::FONT_STYLE_SEMIBOLD, $this->pdfBranding()->secondary);
        $pdf->setFontSpacing(0.8);
        $pdf->setXY(14, 206);
        $pdf->Cell(184, 5, mb_strtoupper($documentName), 0, 0, 'L');
        $pdf->setFontSpacing(0);

        $titleFontSize = 48;
        $this->setFont($pdf, $titleFontSize, self::FONT_STYLE_MEDIUM, $this->pdfBranding()->subtle);
        while ($titleFontSize > 24 && $pdf->getNumLines($title, 184) > 2) {
            $titleFontSize -= 4;
            $this->setFont($pdf, $titleFontSize, self::FONT_STYLE_MEDIUM, $this->pdfBranding()->subtle);
        }
        $pdf->setXY(14, 217);
        $pdf->MultiCell(184, $titleFontSize * 0.375, $title, 0, 'L');

        $this->setFont($pdf, 18, self::FONT_STYLE_MEDIUM, $this->pdfBranding()->secondary);
        $pdf->setX(14);
        $pdf->Cell(184, 10, $date, 0, 0, 'L');

        $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, $this->pdfBranding()->subtle);
        $pdf->setFontSpacing(0.6);
        $pdf->setXY(14, 282);
        $pdf->Cell(90, 5, $footerLeft, 0, 0, 'L');
        $pdf->setXY(104, 282);
        $pdf->Cell(94, 5, mb_strtoupper($documentName), 0, 0, 'R');
        $pdf->setFontSpacing(0);
    }

    private function renderContentPageChrome(\TCPDF $pdf, string $documentName): void
    {
        $pdf->Rect(0, 36, 2.4, 220, 'F', [], $this->pdfBranding()->secondary);

        $logoPath = $this->getImagePath('logo-dark.png');
        if (is_file($logoPath)) {
            $pdf->Image($logoPath, 14, 12, 42, 14, '', '', '', false, 300);
        }

        $this->setFont($pdf, 8, self::FONT_STYLE_SEMIBOLD, $this->pdfBranding()->primary);
        $pdf->setFontSpacing(0.6);
        $pdf->setXY(110, 16);
        $pdf->Cell(86, 5, mb_strtoupper($documentName), 0, 0, 'R');
        $pdf->setFontSpacing(0);

        $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, $this->pdfBranding()->primary);
        $pdf->setXY(14, 282);
        $pdf->Cell(120, 5, mb_strtoupper($documentName) . ' · BIOSCORE SUSTAINABILITY', 0, 0, 'L');

        $pdf->Circle(174, 284.5, 1.2, 0, 360, 'F', [], $this->pdfBranding()->secondary);
        $pdf->setXY(178, 282);
        $pdf->Cell(18, 5, $pdf->getAliasNumPage() . ' / ' . $pdf->getAliasNbPages(), 0, 0, 'L');
    }

    private function renderSectionTitle(\TCPDF $pdf, string $title): void
    {
        $pdf->setXY(16, max(self::CONTENT_MARGIN_TOP, $pdf->GetY()));
        $this->setFont($pdf, 26, self::FONT_STYLE_REGULAR, $this->pdfBranding()->bodyText);

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
        $pdf->Line(16, $y, 44, $y, ['width' => 0.9, 'color' => $this->pdfBranding()->secondary]);
        $pdf->Line(44, $y, 192, $y, ['width' => 0.2, 'color' => $this->pdfBranding()->faintLine]);

        $pdf->setY($y + 6);
    }

    private function markdownToStyledHtml(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter();
        $html = (string) $converter->convert($markdown);

        $html = preg_replace_callback(
            '/<h1>(.*?)<\/h1>/s',
            fn(array $matches) => sprintf('<h1><font color="%s">%s</font></h1>', $this->pdfBranding()->primaryHex(), $matches[1]),
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/<h2>(.*?)<\/h2>/s',
            fn(array $matches) => sprintf('<h2>&middot; <font color="%s">%s</font></h2>', $this->pdfBranding()->primaryHex(), $matches[1]),
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/<h3>(.*?)<\/h3>/s',
            fn(array $matches) => sprintf('<h3><font color="%s">%s</font></h3>', $this->pdfBranding()->primaryHex(), mb_strtoupper($matches[1])),
            $html,
        ) ?? $html;

        $html = str_replace('<strong>', sprintf('<strong><font color="%s">', $this->pdfBranding()->primaryHex()), $html);
        $html = str_replace('</strong>', '</font></strong>', $html);

        $html = preg_replace('/<li><p>(.*?)<\/p><\/li>/s', '<li>$1</li>', $html) ?? $html;
        $html = str_replace('<li>', sprintf('<li><font color="%s">', $this->pdfBranding()->bodyTextHex()), $html);
        $html = str_replace('</li>', '</font></li>', $html);

        $html = str_replace('<p>', sprintf('<p><font color="%s">', $this->pdfBranding()->bodyTextHex()), $html);

        return str_replace('</p>', '</font></p>', $html);
    }
}
