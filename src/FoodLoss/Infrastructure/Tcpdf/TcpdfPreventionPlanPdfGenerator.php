<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Tcpdf;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanPdfGenerator;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanSection;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Waste\Infrastructure\Tcpdf\GenericGenerator;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Contracts\Translation\TranslatorInterface;

class TcpdfPreventionPlanPdfGenerator implements PreventionPlanPdfGenerator
{
    use GenericGenerator;

    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_DARK_TEAL = [31, 72, 88];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_DARK_TEAL_SHADE = [44, 90, 108];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_LIGHT_TEAL_SHADE = [75, 120, 141];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_LIME_RGB = [193, 209, 24];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_TEAL_RGB = [64, 115, 142];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_CREAM = [244, 239, 224];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_BODY_TEXT = [55, 65, 75];
    /** @var array{0: int, 1: int, 2: int} */
    private const COLOR_FAINT_LINE = [220, 225, 230];

    private const HEX_LIME = '#C1D118';
    private const HEX_TEAL = '#40738E';
    private const HEX_BODY = '#37414B';

    public function __construct(private TranslatorInterface $translator) {}

    public function generate(PreventionPlan $preventionPlan, PreventionPlanVersion $version, Locale $locale): string
    {
        $originalLocale = LocaleGetter::locale();

        LocaleGetter::setLocale($locale);

        $planName = $preventionPlan->name();
        $planNameUpper = mb_strtoupper($planName);
        $dateFormatted = (new \DateTimeImmutable())->format('d / m / Y');

        $pdf = $this->createPDF($planName);

        $this->renderCover($pdf, $planName, $dateFormatted);

        /** @var PreventionPlanSection[] $sections */
        $sections = $version->sections()->toArray();

        usort($sections, fn(PreventionPlanSection $a, PreventionPlanSection $b) => $a->sectionOrder() <=> $b->sectionOrder());

        foreach ($sections as $section) {
            $pdf->AddPage();
            $this->renderContentPageChrome($pdf, $planNameUpper);

            $this->renderSectionTitle($pdf, $section->title());

            $this->setFont($pdf, 10.5, self::FONT_STYLE_REGULAR);
            $pdf->setTextColor(self::COLOR_BODY_TEXT[0], self::COLOR_BODY_TEXT[1], self::COLOR_BODY_TEXT[2]);

            $html = $this->markdownToStyledHtml($section->content());

            $this->secureMultiCell($pdf, $html, 178, function (\TCPDF $pdf) use ($planNameUpper): void {
                $pdf->AddPage();
                $this->renderContentPageChrome($pdf, $planNameUpper);
                $pdf->setY(40);
            });
        }

        $path = '/tmp/' . $preventionPlan->id() . '-v' . $version->versionNumber() . '-' . $locale->value . '-plan.pdf';

        $this->savePDF($pdf, $path);

        LocaleGetter::setLocale($originalLocale);

        return $path;
    }

    private function renderCover(\TCPDF $pdf, string $planName, string $dateFormatted): void
    {
        $pdf->AddPage();

        $pdf->Rect(0, 0, 210, 297, 'F', [], self::COLOR_DARK_TEAL);

        $pdf->Circle(174, 26, 4.8, 0, 360, 'F', [], self::COLOR_LIME_RGB);
        $pdf->Circle(186, 40, 7.0, 0, 360, 'F', [], self::COLOR_LIME_RGB);
        $pdf->Circle(170, 50, 3.4, 0, 360, 'F', [], self::COLOR_LIME_RGB);

        $pdf->Circle(71, 237, 95, 0, 360, 'F', [], self::COLOR_DARK_TEAL_SHADE);

        $pdf->Circle(52, 230, 28, 0, 360, 'F', [], self::COLOR_LIGHT_TEAL_SHADE);
        $pdf->Circle(28, 271, 10, 0, 360, 'F', [], self::COLOR_LIGHT_TEAL_SHADE);

        $pdf->Image($this->getImagePath('logo-white.png'), 20, 20, 60, 20, '', '', '', false, 300, '', false, false, 0);

        $pdf->setXY(20, 45);
        $this->setFont($pdf, 8, self::FONT_STYLE_MEDIUM, self::COLOR_CREAM);
        $pdf->setFontSpacing(0.6);
        $pdf->Cell(180, 4, mb_strtoupper($this->translator->trans(id: 'Plan de Prevención del Desperdicio Alimentario', locale: LocaleGetter::locale()->value)), 0, 0, 'L');
        $pdf->setFontSpacing(0);

        $pdf->SetDrawColor(self::COLOR_LIME_RGB[0], self::COLOR_LIME_RGB[1], self::COLOR_LIME_RGB[2]);
        $pdf->SetLineWidth(0.9);
        $pdf->Line(18, 214, 28, 214);

        $pdf->setXY(32, 211);
        $this->setFont($pdf, 9, self::FONT_STYLE_SEMIBOLD, self::COLOR_LIME_RGB);
        $pdf->setFontSpacing(0.8);
        $pdf->Cell(80, 6, mb_strtoupper($this->translator->trans(id: 'Documento Oficial', locale: LocaleGetter::locale()->value)), 0, 0, 'L');
        $pdf->setFontSpacing(0);

        $pdf->setXY(14, 217);
        $this->setFont($pdf, 48, self::FONT_STYLE_MEDIUM, self::COLOR_CREAM);
        $pdf->MultiCell(184, 18, $planName, 0, 'L');

        $pdf->setX(14);
        $this->setFont($pdf, 18, self::FONT_STYLE_REGULAR, self::COLOR_LIME_RGB);
        $pdf->Cell(0, 10, $dateFormatted, 0, 0, 'L');

        $pdf->setXY(14, 282);
        $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_CREAM);
        $pdf->setFontSpacing(0.6);
        $pdf->Cell(60, 5, 'BIOSCORE.INFO', 0, 0, 'L');

        $pdf->setXY(136, 282);
        $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_CREAM);
        $pdf->setFontSpacing(0.6);
        $pdf->Cell(60, 5, mb_strtoupper($planName), 0, 0, 'R');
        $pdf->setFontSpacing(0);
    }

    private function renderContentPageChrome(\TCPDF $pdf, string $planNameUpper): void
    {
        $pdf->Rect(0, 36, 2.4, 220, 'F', [], self::COLOR_LIME_RGB);

        $pdf->Image($this->getImagePath('logo-dark.png'), 14, 12, 42, 14, '', '', '', false, 300, '', false, false, 0);

        $pdf->setXY(110, 16);
        $this->setFont($pdf, 8, self::FONT_STYLE_SEMIBOLD, self::COLOR_TEAL_RGB);
        $pdf->setFontSpacing(0.6);
        $pdf->Cell(86, 5, $planNameUpper, 0, 0, 'R');
        $pdf->setFontSpacing(0);

        // Footer: left text
        $pdf->setXY(14, 282);
        $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_TEAL_RGB);
        $pdf->setFontSpacing(0.6);
        $pdf->Cell(140, 5, $planNameUpper . ' · BIOSCORE SUSTAINABILITY', 0, 0, 'L');

        // Footer: lime dot + page numbering (right)
        $pdf->Circle(174, 284.5, 1.2, 0, 360, 'F', [], self::COLOR_LIME_RGB);
        $pdf->setXY(177, 282);
        $this->setFont($pdf, 7, self::FONT_STYLE_MEDIUM, self::COLOR_TEAL_RGB);
        $pdf->Cell(20, 5, $pdf->getAliasNumPage() . ' / ' . $pdf->getAliasNbPages(), 0, 0, 'L');
        $pdf->setFontSpacing(0);
    }

    private function renderSectionTitle(\TCPDF $pdf, string $title): void
    {
        $pdf->setXY(16, 40);
        $this->setFont($pdf, 26, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);

        $maxWidth = 170;

        $totalWidth = $pdf->GetStringWidth($title);

        if ($totalWidth > $maxWidth && $totalWidth < ($maxWidth * 2)) {
            // Buscamos el punto medio ideal en términos de ancho
            $targetWidth = $totalWidth / 2;
            $words = explode(' ', $title);
            $line1 = '';
            $currentWidth = 0;
            $bestSplitIndex = 0;

            // Recorremos las palabras acumulando su ancho
            foreach ($words as $index => $word) {
                $wordWidth = $pdf->GetStringWidth($word . ' ');
                // Si añadir esta palabra nos acerca más al centro que la anterior, seguimos
                if (abs($currentWidth + $wordWidth - $targetWidth) < abs($currentWidth - $targetWidth)) {
                    $currentWidth += $wordWidth;
                    $bestSplitIndex = $index;
                } else {
                    break;
                }
            }

            // Reconstruimos las dos líneas
            $part1 = implode(' ', array_slice($words, 0, $bestSplitIndex + 1));
            $part2 = implode(' ', array_slice($words, $bestSplitIndex + 1));
            $balancedTitle = $part1 . "\n" . $part2;

            $pdf->MultiCell($maxWidth, 12, $balancedTitle, 0, 'L');
        } else {
            // Si es corto o demasiado largo (más de 2 líneas), usamos el comportamiento normal
            $pdf->MultiCell($maxWidth, 12, $title, 0, 'L');
        }


        $y = $pdf->getY() + 2;
        $pdf->SetDrawColor(self::COLOR_LIME_RGB[0], self::COLOR_LIME_RGB[1], self::COLOR_LIME_RGB[2]);
        $pdf->SetLineWidth(0.9);
        $pdf->Line(16, $y, 44, $y);
        $pdf->SetDrawColor(self::COLOR_FAINT_LINE[0], self::COLOR_FAINT_LINE[1], self::COLOR_FAINT_LINE[2]);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(44, $y, 192, $y);
        $pdf->setY($y + 6);
    }

    private function markdownToStyledHtml(string $markdown): string
    {
        $html = (new GithubFlavoredMarkdownConverter())->convert($markdown)->getContent();

        $html = preg_replace_callback(
            '/<h2[^>]*>(.*?)<\/h2>/s',
            fn(array $m): string => '<h2><font color="' . self::HEX_LIME . '">&middot;</font>  <font color="' . self::HEX_TEAL . '">' . $m[1] . '</font></h2>',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/<h3[^>]*>(.*?)<\/h3>/s',
            fn(array $m): string => '<h3><font color="' . self::HEX_TEAL . '">' . mb_strtoupper(strip_tags($m[1])) . '</font></h3>',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/<h1[^>]*>(.*?)<\/h1>/s',
            fn(array $m): string => '<h1><font color="' . self::HEX_TEAL . '">' . $m[1] . '</font></h1>',
            $html,
        ) ?? $html;

        $html = str_replace('<strong>', '<strong><font color="' . self::HEX_TEAL . '">', $html);
        $html = str_replace('</strong>', '</font></strong>', $html);

        $html = preg_replace('/<li>\s*<p[^>]*>/i', '<li>', $html) ?? $html;
        $html = preg_replace('/<\/p>\s*<\/li>/i', '</li>', $html) ?? $html;

        $html = str_replace('<p>', '<p><font color="' . self::HEX_BODY . '">', $html);
        $html = str_replace('</p>', '</font></p>', $html);

        $html = str_replace('<li>', '<li><font color="' . self::HEX_LIME . '"> </font><font color="' . self::HEX_BODY . '">', $html);
        $html = str_replace('</li>', '</font></li>', $html);

        return $html;
    }
}
