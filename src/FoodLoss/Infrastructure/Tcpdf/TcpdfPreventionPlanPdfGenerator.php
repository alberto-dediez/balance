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

class TcpdfPreventionPlanPdfGenerator implements PreventionPlanPdfGenerator
{
    use GenericGenerator;

    public function generate(PreventionPlan $preventionPlan, PreventionPlanVersion $version, Locale $locale): string
    {
        $originalLocale = LocaleGetter::locale();

        LocaleGetter::setLocale($locale);

        $pdf = $this->createPDF($preventionPlan->name());

        $pdf->AddPage();

        $pdf->Image($this->getImagePath('logo-vertical.png'), 76, 12, 65, 60, '', '', '', false, 300, '', false, false, 0);

        $pdf->setXY(0, 80);
        $this->setFont($pdf, 48, self::FONT_STYLE_BOLD, self::COLOR_PRIMARY);
        $pdf->MultiCell(w: 210, h: 20, txt: mb_strtoupper($preventionPlan->name()), border: 0, align: 'C');

        $pdf->setXY(0, 205);
        $this->setFont($pdf, 24, self::FONT_STYLE_REGULAR, self::COLOR_NEUTRAL_900);
        $pdf->MultiCell(w: 210, h: 10, txt: date('d/m/Y'), border: 0, align: 'C');

        /** @var PreventionPlanSection[] $sections */
        $sections = $version->sections()->toArray();

        usort($sections, fn(PreventionPlanSection $a, PreventionPlanSection $b) => $a->sectionOrder() <=> $b->sectionOrder());

        foreach ($sections as $section) {
            $pdf->AddPage();

            $pdf->setY(16);

            $this->setFont($pdf, 11, self::FONT_STYLE_REGULAR, self::COLOR_NEUTRAL_900);

            $html = $this->markdownToHtml($section->content());

            $this->secureMultiCell($pdf, $html, 178, function (\TCPDF $pdf): void {
                $pdf->AddPage();
                $pdf->setY(16);
            });
        }

        $path = '/tmp/' . $preventionPlan->id() . '-v' . $version->versionNumber() . '-' . $locale->value . '-plan.pdf';

        $this->savePDF($pdf, $path);

        LocaleGetter::setLocale($originalLocale);

        return $path;
    }

    private function markdownToHtml(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter();

        return $converter->convert($markdown)->getContent();
    }
}
