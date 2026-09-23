<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\Shared\Domain\Model\Locale\Locale;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: TcpdfCarbonFootprintReportPdfGenerator::class)]
class ThemeAwareCarbonFootprintReportPdfGenerator implements CarbonFootprintReportPdfGenerator
{
    public function __construct(
        private readonly CarbonFootprintReportPdfGenerator $inner,
        private readonly ContainerInterface $themeGenerators,
    ) {}

    public function generate(
        CarbonFootprintReportData $data,
        CarbonFootprintReportTexts $texts,
        CarbonFootprintReportCharts $charts,
        Locale $locale,
        string $themeSlug,
    ): string {
        return $this->generatorFor($themeSlug)->generate($data, $texts, $charts, $locale, $themeSlug);
    }

    private function generatorFor(string $themeSlug): CarbonFootprintReportPdfGenerator
    {
        if (!$this->themeGenerators->has($themeSlug)) {
            return $this->inner;
        }

        $generator = $this->themeGenerators->get($themeSlug);

        return $generator instanceof CarbonFootprintReportPdfGenerator ? $generator : $this->inner;
    }
}
