<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Tcpdf;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateData;
use App\Balance\Infrastructure\Tcpdf\TcpdfContributionCertificatePdfGenerator;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Qr\QrCodeGenerator;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateDataMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingTokensMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

final class TcpdfContributionCertificatePdfGeneratorTest extends TestCase
{
    private const string PREVIEW_DIRECTORY = 'var/certificate-preview';

    public function testItDrawsACompensationCertificate(): void
    {
        $this->assertPreviewIsAPdf(ContributionCertificateDataMother::compensation(), Locale::Spanish, 'compensacion');
    }

    public function testItDrawsASocialContributionCertificate(): void
    {
        $this->assertPreviewIsAPdf(ContributionCertificateDataMother::socialContribution(), Locale::Spanish, 'aportacion-social');
    }

    public function testItDrawsACertificateWithoutOptionalBlocks(): void
    {
        $this->assertPreviewIsAPdf(ContributionCertificateDataMother::withoutOptionalBlocks(), Locale::Spanish, 'minimo');
    }

    public function testItDrawsACompensationCertificateInEnglish(): void
    {
        $this->assertPreviewIsAPdf(ContributionCertificateDataMother::compensation(), Locale::English, 'compensacion');
    }

    public function testItDrawsABrandedCertificate(): void
    {
        $branding = PdfBranding::fromTokens(
            BrandingTokensMother::branded(),
            'Acme Sustainability',
        );

        $this->assertPreviewIsAPdf(ContributionCertificateDataMother::compensation(), Locale::Spanish, 'marca-blanca', $branding);
    }

    private function assertPreviewIsAPdf(ContributionCertificateData $data, Locale $locale, string $name, ?PdfBranding $branding = null): void
    {
        $path = $this->generator()->generate($data, $locale, $branding ?? PdfBranding::bioscore());

        try {
            $this->assertFileExists($path);

            $contents = (string) file_get_contents($path);

            $this->assertStringStartsWith('%PDF-', $contents);
            $this->assertStringEndsWith("%%EOF\n", $contents);

            copy($path, $this->previewPath($name . '-' . $locale->value));
        } finally {
            @unlink($path);
        }
    }

    private function generator(): TcpdfContributionCertificatePdfGenerator
    {
        return new TcpdfContributionCertificatePdfGenerator($this->translator(), new QrCodeGenerator());
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
