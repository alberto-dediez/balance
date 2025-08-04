<?php

namespace App\Waste\Infrastructure\Tcpdf;

use \TCPDF;
use \TCPDF_FONTS;
use Symfony\Component\DomCrawler\Crawler;

trait GenericGenerator
{
    const COLOR_PRIMARY = 'blue';
    const COLOR_SECONDARY = 'green';
    const COLOR_NEUTRAL_100 = 'white';
    const COLOR_NEUTRAL_900 = 'black';

    const FONT_STYLE_BOLD = 'bold';
    const FONT_STYLE_SEMIBOLD = 'semi-bold';
    const FONT_STYLE_BLACK = 'black';
    const FONT_STYLE_REGULAR = 'regular';
    const FONT_STYLE_BOLDITALIC = 'bold-italic';
    const FONT_STYLE_MEDIUM = 'medium';

    private function createPDF(string $title): TCPDF
    {
        $pdf = new TCPDF('P');
        $pdf->setCreator('Bioscore Sustainability');
        $pdf->setAuthor('Bioscore Sustainability');
        $pdf->setTitle($title);      
        $pdf->setMargins(0, 0, 0, true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setAutoPageBreak(false, 0);
        
        return $pdf;
    }

    private function setFont(TCPDF $pdf, $size, $type = self::FONT_STYLE_REGULAR, $color = self::COLOR_PRIMARY)
    {
        switch ($type) {
            case self::FONT_STYLE_BOLD:
                $fontName = 'Rubik-Bold';
                break;
            case self::FONT_STYLE_SEMIBOLD:
                $fontName = 'Rubik-SemiBold';
                break;
            case self::FONT_STYLE_BLACK:
                $fontName = 'Rubik-Black';
                break;
            case self::FONT_STYLE_REGULAR:
                $fontName = 'Rubik-Regular';
                break;   
            case self::FONT_STYLE_BOLDITALIC:
                $fontName = 'Rubik-BoldItalic';
                break;   
            case self::FONT_STYLE_MEDIUM:
            default:
                $fontName = 'Rubik-Medium';
                break;            
        }

        $font = TCPDF_FONTS::addTTFfont($this->getFontPath($fontName), 'TrueTypeUnicode', '', 96);

        $pdf->setFont($font, '', $size, '', false);
        $pdf->setFontSpacing(0);

        if (is_array($color) && count($color) == 3) {
            $pdf->setTextColor($color[0], $color[1], $color[2]);
        } else {
            switch ($color) {
                case self::COLOR_SECONDARY:
                    $pdf->setTextColor(193, 209, 24);
                    break;
                case self::COLOR_NEUTRAL_100:
                    $pdf->setTextColor(255, 255, 255);
                    break;
                case self::COLOR_NEUTRAL_900:
                    $pdf->setTextColor(0, 0, 0);
                    break;
                case self::COLOR_PRIMARY:
                default:
                    $pdf->setTextColor(64, 115, 142);
                    break;
            }
        }                
    }

    private function hex2rgb(string $hexColor): array
    {
        return sscanf($hexColor, "#%02x%02x%02x");
    }

    private function getRootDir(): string
    {
        return __DIR__ . '/../../../../';
    }

    private function getFontPath(string $fontName): string
    {
        return sprintf("%sassets/fonts/%s.ttf", $this->getRootDir(), $fontName);
    }

    private function getImagePath(string $filename): string
    {
        return sprintf("%sassets/images/%s", $this->getRootDir(), $filename);
    }

    private function secureMultiCell(TCPDF $pdf, string $html, $width, \Closure $createNewPage)
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
                true
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
                true
            );

            /** @var DomNode $element */
            if ($element->tagName == 'p' || $element->tagName == 'ul') {
                $pdf->SetY($pdf->getY() + 5);
            }
        }
    }

    private function savePDF(TCPDF $pdf, string $path)
    {
        $pdf->Output($path, 'F');
    }
}