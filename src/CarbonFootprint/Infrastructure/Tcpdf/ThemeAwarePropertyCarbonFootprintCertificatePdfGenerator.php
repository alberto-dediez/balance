<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateData;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificatePdfGenerator;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: TcpdfPropertyCarbonFootprintCertificatePdfGenerator::class)]
class ThemeAwarePropertyCarbonFootprintCertificatePdfGenerator implements PropertyCarbonFootprintCertificatePdfGenerator
{
    public function __construct(
        private readonly PropertyCarbonFootprintCertificatePdfGenerator $inner,
        private readonly ContainerInterface $themeGenerators,
    ) {}

    public function generate(PropertyCarbonFootprintCertificateData $data, Locale $locale, PdfBranding $branding): string
    {
        return $this->generatorFor($branding->themeSlug)->generate($data, $locale, $branding);
    }

    private function generatorFor(string $themeSlug): PropertyCarbonFootprintCertificatePdfGenerator
    {
        if (!$this->themeGenerators->has($themeSlug)) {
            return $this->inner;
        }

        $generator = $this->themeGenerators->get($themeSlug);

        return $generator instanceof PropertyCarbonFootprintCertificatePdfGenerator ? $generator : $this->inner;
    }
}
