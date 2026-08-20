<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate\Send;

use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateSent;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContributionCertificateRepository;
use App\Balance\Infrastructure\Storage\InMemoryContributionCertificateStorage;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateMother;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Deliver\SpyEmailSender;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Translation\Translator;

class SendContributionCertificateServiceTest extends TestCase
{
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

    public function testTheDocumentTravelsAsAnAttachment(): void
    {
        $certificate = $this->givenGeneratedCertificate();
        $spyEmailSender = new SpyEmailSender();
        $spySubscriber = new SpyDomainEventSubscriber();

        $this->send($certificate, $spyEmailSender);

        $sent = $spyEmailSender->sent();

        $this->assertCount(1, $sent);
        $this->assertSame($certificate->contactEmail(), $sent[0]['recipient']->email);
        $this->assertCount(1, $sent[0]['attachments']);
        $this->assertSame(sprintf('certificado-%s.pdf', $certificate->code()), $sent[0]['attachments'][0]->filename);
        $this->assertInstanceOf(ContributionCertificateSent::class, $spySubscriber->lastEvent());
    }

    public function testTheDocumentCanBeSentToAnotherAddress(): void
    {
        $certificate = $this->givenGeneratedCertificate();
        $spyEmailSender = new SpyEmailSender();

        $this->send($certificate, $spyEmailSender, 'someone.else@example.com');

        $this->assertSame('someone.else@example.com', $spyEmailSender->sent()[0]['recipient']->email);
    }

    public function testASocialCertificateGetsItsOwnSubject(): void
    {
        $certificate = $this->givenGeneratedCertificate(ContributionCertificateType::SocialContribution);
        $spyEmailSender = new SpyEmailSender();

        $this->send($certificate, $spyEmailSender);

        $this->assertSame('Tu certificado de aportación', $spyEmailSender->sent()[0]['subject']);
    }

    public function testAnUngeneratedCertificateIsNotSent(): void
    {
        $certificate = ContributionCertificateMother::issued();
        $this->certificates->add($certificate);

        $this->expectException(\InvalidArgumentException::class);

        $this->send($certificate, new SpyEmailSender());
    }

    public function testAnInvalidAddressIsRejected(): void
    {
        $certificate = $this->givenGeneratedCertificate();

        $this->expectException(\InvalidArgumentException::class);

        $this->send($certificate, new SpyEmailSender(), 'not-an-email');
    }

    private function givenGeneratedCertificate(
        ContributionCertificateType $type = ContributionCertificateType::Compensation,
    ): ContributionCertificate {
        $certificate = ContributionCertificateMother::issued(type: $type);

        $localPath = sprintf('%s/%s.pdf', sys_get_temp_dir(), uniqid('certificate-fixture-', true));
        file_put_contents($localPath, 'pdf');

        $path = $this->storage->store($certificate->id(), $certificate->accountId(), Locale::Spanish, $localPath);
        @unlink($localPath);

        $certificate->markGenerated([Locale::Spanish->value => $path]);
        $this->certificates->add($certificate);

        return $certificate;
    }

    private function send(ContributionCertificate $certificate, SpyEmailSender $emailSender, ?string $email = null): void
    {
        (new SendContributionCertificateService(
            $this->certificates,
            $this->storage,
            $emailSender,
            new Translator('es'),
            new LocaleSwitcher('es', []),
            new ResolveBrandingService(
                BrandingRepositoryMother::inMemory(),
                ThemeRegistryMother::withThemes(),
                new BrandingPalette(),
            ),
        ))(new SendContributionCertificateRequest($certificate->id(), $email));
    }
}
