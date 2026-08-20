<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Tcpdf;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateData;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificatePdfGenerator;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: TcpdfContributionCertificatePdfGenerator::class)]
class ThemeAwareContributionCertificatePdfGenerator implements ContributionCertificatePdfGenerator
{
    public function __construct(
        private readonly ContributionCertificatePdfGenerator $inner,
        private readonly ContainerInterface $themeGenerators,
    ) {}

    public function generate(ContributionCertificateData $data, Locale $locale, PdfBranding $branding): string
    {
        return $this->generatorFor($branding->themeSlug)->generate($data, $locale, $branding);
    }

    private function generatorFor(string $themeSlug): ContributionCertificatePdfGenerator
    {
        if (!$this->themeGenerators->has($themeSlug)) {
            return $this->inner;
        }

        $generator = $this->themeGenerators->get($themeSlug);

        return $generator instanceof ContributionCertificatePdfGenerator ? $generator : $this->inner;
    }
}
