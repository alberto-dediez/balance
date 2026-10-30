<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Generate;

use App\Balance\Application\ContributionCertificate\Generate\CertificateLogoDownloader;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Generate\GeneratePropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Generate\GeneratePropertyCarbonFootprintCertificateService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStatus;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryPropertyCarbonFootprintCertificateStorage;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;

class GeneratePropertyCarbonFootprintCertificateServiceTest extends TestCase
{
    private const string LOCALE_LIST = 'es|en';

    private InMemoryPropertyCarbonFootprintCertificateRepository $certificates;
    private InMemoryPropertyCarbonFootprintCertificateStorage $storage;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->certificates = PropertyCarbonFootprintCertificateRepositoryMother::inMemory();
        $this->storage = new InMemoryPropertyCarbonFootprintCertificateStorage();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testOneFileIsStoredPerLocale(): void
    {
        $certificate = $this->givenCertificate(PropertyCarbonFootprintCertificateMother::issued());

        $pdfGenerator = new FakePropertyCarbonFootprintCertificatePdfGenerator();
        $this->generate($certificate, $pdfGenerator);

        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Generated, $certificate->status());
        $this->assertCount(2, $this->storage->paths());
        $this->assertNotNull($certificate->fileFor(Locale::Spanish));
        $this->assertNotNull($certificate->fileFor(Locale::English));
        $this->assertNotSame($certificate->fileFor(Locale::Spanish), $certificate->fileFor(Locale::English));
    }

    public function testTheDocumentCarriesTheCertifiedFiguresAndTheVerificationUrl(): void
    {
        $certificate = $this->givenCertificate(PropertyCarbonFootprintCertificateMother::issued(stays: 41000, totalCo2eqKg: 184320.55));

        $pdfGenerator = new FakePropertyCarbonFootprintCertificatePdfGenerator();
        $this->generate($certificate, $pdfGenerator);

        $data = $pdfGenerator->rendered()[0];

        $this->assertSame($certificate->code(), $data->code);
        $this->assertSame(4.50, $data->co2eqKgPerStay);
        $this->assertSame(41000, $data->stays);
        $this->assertSame(2025, $data->year);
        $this->assertStringContainsString($certificate->verificationToken(), (string) $data->verificationUrl);
        $this->assertStringNotContainsString($certificate->code(), (string) $data->verificationUrl);
    }

    public function testAFailureCleansUpWhatWasAlreadyStoredAndRethrows(): void
    {
        $certificate = $this->givenCertificate(PropertyCarbonFootprintCertificateMother::issued());

        try {
            $this->generate($certificate, new FakePropertyCarbonFootprintCertificatePdfGenerator(failOn: Locale::English));
            $this->fail('The generation was expected to fail.');
        } catch (\RuntimeException) {
        }

        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Failed, $certificate->status());
        $this->assertSame('rendering failure', $certificate->failureReason());
        $this->assertSame([], $this->storage->paths());
        $this->assertCount(1, $this->storage->deleted());
    }

    public function testAnAlreadyGeneratedCertificateIsLeftUntouched(): void
    {
        $certificate = $this->givenCertificate(PropertyCarbonFootprintCertificateMother::generated());

        $this->generate($certificate, new FakePropertyCarbonFootprintCertificatePdfGenerator(failOn: Locale::Spanish));

        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Generated, $certificate->status());
        $this->assertSame([], $this->storage->paths());
    }

    public function testARevokedCertificateIsLeftUntouched(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::issued();
        $certificate->revoke();
        $this->givenCertificate($certificate);

        $this->generate($certificate, new FakePropertyCarbonFootprintCertificatePdfGenerator());

        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Revoked, $certificate->status());
        $this->assertSame([], $this->storage->paths());
    }

    public function testACertificateRevokedDuringTheGenerationStaysRevokedWithoutFiles(): void
    {
        $certificate = $this->givenCertificate(PropertyCarbonFootprintCertificateMother::issued());

        $this->generate($certificate, new FakePropertyCarbonFootprintCertificatePdfGenerator(
            onGenerate: static fn() => $certificate->revoke(),
        ));

        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Revoked, $certificate->status());
        $this->assertSame([], $certificate->files());
        $this->assertSame([], $this->storage->paths());
        $this->assertCount(2, $this->storage->deleted());
    }

    public function testACertificateRevokedBeforeAGenerationFailureStaysRevokedWithoutFilesAndRethrows(): void
    {
        $certificate = $this->givenCertificate(PropertyCarbonFootprintCertificateMother::issued());

        try {
            $this->generate($certificate, new FakePropertyCarbonFootprintCertificatePdfGenerator(
                failOn: Locale::English,
                onGenerate: static fn() => $certificate->revoke(),
            ));
            $this->fail('The generation was expected to fail.');
        } catch (\RuntimeException $e) {
            $this->assertSame('rendering failure', $e->getMessage());
        }

        $this->assertSame(PropertyCarbonFootprintCertificateStatus::Revoked, $certificate->status());
        $this->assertNull($certificate->failureReason());
        $this->assertSame([], $certificate->files());
        $this->assertSame([], $this->storage->paths());
        $this->assertCount(1, $this->storage->deleted());
    }

    private function givenCertificate(PropertyCarbonFootprintCertificate $certificate): PropertyCarbonFootprintCertificate
    {
        $this->certificates->add($certificate);

        return $certificate;
    }

    private function generate(PropertyCarbonFootprintCertificate $certificate, FakePropertyCarbonFootprintCertificatePdfGenerator $pdfGenerator): void
    {
        (new GeneratePropertyCarbonFootprintCertificateService(
            $this->certificates,
            PropertyRepositoryMother::inMemory(),
            DestinationRepositoryMother::inMemory(),
            CountryRepositoryMother::inMemory(),
            $pdfGenerator,
            $this->storage,
            new FakePropertyCarbonFootprintCertificateVerificationUrlGenerator(),
            new ResolveBrandingService(
                BrandingRepositoryMother::inMemory(),
                ThemeRegistryMother::withThemes(),
                new BrandingPalette(),
            ),
            new CertificateLogoDownloader(MediaRepositoryMother::inMemory(), MediaStorageMother::inMemory()),
            ThemeRegistryMother::withThemes(),
            self::LOCALE_LIST,
            'Bioscore Sustainability',
        ))(new GeneratePropertyCarbonFootprintCertificateRequest($certificate->id()), UserMother::systemUser());
    }
}
