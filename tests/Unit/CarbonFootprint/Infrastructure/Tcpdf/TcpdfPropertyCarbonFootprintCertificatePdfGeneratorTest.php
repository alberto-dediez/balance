<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateData;
use App\CarbonFootprint\Infrastructure\Tcpdf\TcpdfPropertyCarbonFootprintCertificatePdfGenerator;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Qr\QrCodeGenerator;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateDataMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingTokensMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

final class TcpdfPropertyCarbonFootprintCertificatePdfGeneratorTest extends TestCase
{
    private const string PREVIEW_DIRECTORY = 'var/footprint-certificate-preview';

    public function testItDrawsACertificate(): void
    {
        $this->assertPreviewIsAPdf(PropertyCarbonFootprintCertificateDataMother::standard(), Locale::Spanish, 'estandar');
    }

    public function testItDrawsACertificateInEnglish(): void
    {
        $this->assertPreviewIsAPdf(PropertyCarbonFootprintCertificateDataMother::standard(), Locale::English, 'estandar');
    }

    public function testItDrawsACertificateWithScope3Excluded(): void
    {
        $this->assertPreviewIsAPdf(PropertyCarbonFootprintCertificateDataMother::scope3Excluded(), Locale::Spanish, 'alcance-3-excluido');
    }

    public function testItDrawsACertificateWithALongPropertyName(): void
    {
        $this->assertPreviewIsAPdf(PropertyCarbonFootprintCertificateDataMother::longPropertyName(), Locale::Spanish, 'nombre-largo');
    }

    public function testItDrawsABrandedCertificate(): void
    {
        $branding = PdfBranding::fromTokens(
            BrandingTokensMother::branded(),
            'Acme Sustainability',
        );

        $this->assertPreviewIsAPdf(PropertyCarbonFootprintCertificateDataMother::standard(), Locale::Spanish, 'marca-blanca', $branding);
    }

    private function assertPreviewIsAPdf(PropertyCarbonFootprintCertificateData $data, Locale $locale, string $name, ?PdfBranding $branding = null): void
    {
        $path = $this->generator()->generate($data, $locale, $branding ?? PdfBranding::bioscore());

        try {
            $this->assertFileExists($path);
            $this->assertStringContainsString('footprint-certificate-', basename($path));

            $contents = (string) file_get_contents($path);

            $this->assertStringStartsWith('%PDF-', $contents);
            $this->assertStringEndsWith("%%EOF\n", $contents);

            copy($path, $this->previewPath($name . '-' . $locale->value));
        } finally {
            @unlink($path);
        }
    }

    private function generator(): TcpdfPropertyCarbonFootprintCertificatePdfGenerator
    {
        return new TcpdfPropertyCarbonFootprintCertificatePdfGenerator($this->translator(), new QrCodeGenerator());
    }

    private function translator(): Translator
    {
        $translator = new Translator(Locale::Spanish->value);
        $translator->addLoader('xlf', new XliffFileLoader());

        foreach (Locale::cases() as $locale) {
            $catalogue = $this->rootDirectory() . '/translations/messages+intl-icu.' . $locale->value . '.xlf';

            if (is_file($catalogue)) {
                $translator->addResource('xlf', $catalogue, $locale->value, 'messages+intl-icu');
            }
        }

        return $translator;
    }

    private function previewPath(string $name): string
    {
        $directory = $this->rootDirectory() . '/' . self::PREVIEW_DIRECTORY;

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            $this->fail(sprintf('Could not create the preview directory %s', $directory));
        }

        return $directory . '/' . $name . '.pdf';
    }

    private function rootDirectory(): string
    {
        return dirname(__DIR__, 5);
    }
}
