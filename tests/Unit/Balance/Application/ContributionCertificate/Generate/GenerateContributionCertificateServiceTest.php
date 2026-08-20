<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate\Generate;

use App\Balance\Application\ContributionCertificate\Generate\CertificateLogoDownloader;
use App\Balance\Application\ContributionCertificate\Generate\GenerateContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Generate\GenerateContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateStatus;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContributionCertificateRepository;
use App\Balance\Infrastructure\Storage\InMemoryContributionCertificateStorage;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateMother;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;

class GenerateContributionCertificateServiceTest extends TestCase
{
    private const string LOCALE_LIST = 'es|en';

    private InMemoryContributionCertificateRepository $certificates;
    private InMemoryContributionCertificateStorage $storage;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->certificates = ContributionCertificateRepositoryMother::inMemory();
        $this->storage = new InMemoryContributionCertificateStorage();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testOneFileIsStoredPerLocale(): void
    {
        $certificate = $this->givenIssuedCertificate();

        $this->generate($certificate, new FakeContributionCertificatePdfGenerator());

        $this->assertTrue($certificate->isGenerated());
        $this->assertCount(2, $this->storage->paths());
        $this->assertNotNull($certificate->fileFor(Locale::Spanish));
        $this->assertNotNull($certificate->fileFor(Locale::English));
    }

    public function testACompensationCertificateCarriesItsVerificationUrl(): void
    {
        $certificate = $this->givenIssuedCertificate();

        $pdfGenerator = new FakeContributionCertificatePdfGenerator();
        $this->generate($certificate, $pdfGenerator);

        $rendered = $pdfGenerator->rendered();

        $this->assertNotNull($rendered[0]->verificationUrl);
        $this->assertStringContainsString($certificate->verificationToken(), (string) $rendered[0]->verificationUrl);
        $this->assertStringNotContainsString($certificate->code(), (string) $rendered[0]->verificationUrl);
    }

    public function testASocialCertificateCarriesAVerificationUrlToo(): void
    {
        $certificate = $this->givenIssuedCertificate(ContributionCertificateType::SocialContribution);

        $pdfGenerator = new FakeContributionCertificatePdfGenerator();
        $this->generate($certificate, $pdfGenerator);

        $rendered = $pdfGenerator->rendered();

        $this->assertNotNull($rendered[0]->verificationUrl);
        $this->assertStringContainsString($certificate->verificationToken(), (string) $rendered[0]->verificationUrl);
    }

    public function testAFailureCleansUpWhatWasAlreadyStored(): void
    {
        $certificate = $this->givenIssuedCertificate();

        try {
            $this->generate($certificate, new FakeContributionCertificatePdfGenerator(failOn: Locale::English));
            $this->fail('The generation was expected to fail.');
        } catch (\RuntimeException) {
        }

        $this->assertSame(ContributionCertificateStatus::Failed, $certificate->status());
        $this->assertSame('rendering failure', $certificate->failureReason());
        $this->assertSame([], $this->storage->paths());
        $this->assertCount(1, $this->storage->deleted());
    }

    public function testGeneratingAnAlreadyGeneratedCertificateDoesNothing(): void
    {
        $certificate = $this->givenIssuedCertificate();

        $this->generate($certificate, new FakeContributionCertificatePdfGenerator());
        $this->generate($certificate, new FakeContributionCertificatePdfGenerator(failOn: Locale::Spanish));

        $this->assertTrue($certificate->isGenerated());
    }

    private function givenIssuedCertificate(
        ContributionCertificateType $type = ContributionCertificateType::Compensation,
    ): ContributionCertificate {
        $certificate = ContributionCertificateMother::issued(type: $type);

        $this->certificates->add($certificate);

        return $certificate;
    }

    private function generate(ContributionCertificate $certificate, FakeContributionCertificatePdfGenerator $pdfGenerator): void
    {
        (new GenerateContributionCertificateService(
            $this->certificates,
            ProjectRepositoryMother::inMemory(),
            $pdfGenerator,
            $this->storage,
            new FakeContributionCertificateVerificationUrlGenerator(),
            new ResolveBrandingService(
                BrandingRepositoryMother::inMemory(),
                ThemeRegistryMother::withThemes(),
                new BrandingPalette(),
            ),
            new CertificateLogoDownloader(MediaRepositoryMother::inMemory(), MediaStorageMother::inMemory()),
            self::LOCALE_LIST,
            'Bioscore Sustainability',
        ))(new GenerateContributionCertificateRequest($certificate->id()), UserMother::systemUser());
    }
}
