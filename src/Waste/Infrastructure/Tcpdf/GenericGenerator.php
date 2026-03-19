<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Tcpdf;

use Symfony\Component\DomCrawler\Crawler;

trait GenericGenerator
{
    public const COLOR_PRIMARY = 'blue';
    public const COLOR_SECONDARY = 'green';
    public const COLOR_NEUTRAL_100 = 'white';
    public const COLOR_NEUTRAL_900 = 'black';

    public const FONT_STYLE_BOLD = 'bold';
    public const FONT_STYLE_SEMIBOLD = 'semi-bold';
    public const FONT_STYLE_BLACK = 'black';
    public const FONT_STYLE_REGULAR = 'regular';
    public const FONT_STYLE_BOLDITALIC = 'bold-italic';
    public const FONT_STYLE_MEDIUM = 'medium';

    private function createPDF(string $title): \TCPDF
    {
        $pdf = new \TCPDF('P');
        $pdf->setCreator('Bioscore Sustainability');
        $pdf->setAuthor('Bioscore Sustainability');
        $pdf->setTitle($title);
        $pdf->setMargins(0, 0, 0, true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setAutoPageBreak(false, 0);

        return $pdf;
    }

    private function setFont(\TCPDF $pdf, $size, $type = self::FONT_STYLE_REGULAR, $color = self::COLOR_PRIMARY): void
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

        $pdf->setFont($font, '', $size, '', false);
        $pdf->setFontSpacing(0);

        if (is_array($color) && 3 === count($color)) {
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

    private function hex2rgb(string $hexColor): array
    {
        return sscanf($hexColor, '#%02x%02x%02x');
    }

    private function getRootDir(): string
    {
        return __DIR__ . '/../../../../';
    }

    private function getFontPath(string $fontName): string
    {
        return sprintf('%sassets/fonts/%s.ttf', $this->getRootDir(), $fontName);
    }

    private function getImagePath(string $filename): string
    {
        return sprintf('%sassets/images/%s', $this->getRootDir(), $filename);
    }

    private function secureMultiCell(\TCPDF $pdf, string $html, $width, \Closure $createNewPage): void
    {
        $breakPoint = $pdf->getPageHeight() - 10;

        $crawler = new Crawler($html);

        foreach ($crawler->filter('body > *') as $element) {
            $htmlElement = $element->ownerDocument->saveHTML($element);

            $pdf->setX(16);

            $savedPdf = clone $pdf;
            $savedPdf->MultiCell(
                $width,
                0,
                $htmlElement,
                0,
                'L',
                false,
                1,
                '',
                '',
                true,
                null,
                true,
            );

            $afterHeight = $savedPdf->getY();

            if ($afterHeight > $breakPoint) {
                $createNewPage($pdf);
            }

            $pdf->MultiCell(
                $width,
                0,
                $htmlElement,
                0,
                'L',
                false,
                1,
                '',
                '',
                true,
                null,
                true,
            );

            /** @var DomNode $element */
            if ('p' == $element->tagName || 'ul' == $element->tagName) {
                $pdf->SetY($pdf->getY() + 5);
            }
        }
    }

    private function savePDF(\TCPDF $pdf, string $path): void
    {
        $pdf->Output($path, 'F');
    }
}
