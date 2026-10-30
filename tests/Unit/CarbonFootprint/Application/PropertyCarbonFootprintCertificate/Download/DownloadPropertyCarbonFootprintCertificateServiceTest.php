<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download\DownloadPropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download\DownloadPropertyCarbonFootprintCertificateResponse;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download\DownloadPropertyCarbonFootprintCertificateService;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryPropertyCarbonFootprintCertificateStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DownloadPropertyCarbonFootprintCertificateServiceTest extends TestCase
{
    private AccountId $accountId;
    private InMemoryPropertyCarbonFootprintCertificateRepository $certificates;
    private InMemoryPropertyCarbonFootprintCertificateStorage $storage;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->certificates = PropertyCarbonFootprintCertificateRepositoryMother::inMemory();
        $this->storage = new InMemoryPropertyCarbonFootprintCertificateStorage();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testTheGeneratedFileOfTheRequestedLocaleIsServed(): void
    {
        $certificate = $this->generatedCertificate([Locale::Spanish, Locale::English]);

        $response = $this->download($certificate, Locale::English, $this->owner());

        $this->assertSame('pdf-en', stream_get_contents($response->stream));
        $this->assertSame(sprintf('certificado-huella-%s-en.pdf', $certificate->code()), $response->filename);
        $this->assertSame('application/pdf', $response->mimeType);
    }

    public function testALocaleWithoutFileFallsBackToTheIssuingLocale(): void
    {
        $certificate = $this->generatedCertificate([Locale::Spanish]);

        $response = $this->download($certificate, Locale::French, $this->owner());

        $this->assertSame('pdf-es', stream_get_contents($response->stream));
    }

    public function testAPendingCertificateCannotBeDownloaded(): void
    {
        $certificate = PropertyCarbonFootprintCertificateMother::issued(accountId: $this->accountId);
        $this->certificates->add($certificate);

        $this->expectException(\InvalidArgumentException::class);

        $this->download($certificate, Locale::Spanish, $this->owner());
    }

    public function testARevokedCertificateCannotBeDownloaded(): void
    {
        $certificate = $this->generatedCertificate([Locale::Spanish]);
        $certificate->revoke();

        $this->expectException(\InvalidArgumentException::class);

        $this->download($certificate, Locale::Spanish, $this->owner());
    }

    public function testAnOwnerOfAnotherAccountIsNotAuthorized(): void
    {
        $certificate = $this->generatedCertificate([Locale::Spanish]);

        $this->expectException(UnauthorizedActionException::class);

        $this->download($certificate, Locale::Spanish, UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, AccountId::generate()));
    }

    /** @param list<Locale> $locales */
    private function generatedCertificate(array $locales): PropertyCarbonFootprintCertificate
    {
        $certificate = PropertyCarbonFootprintCertificateMother::issued(accountId: $this->accountId);

        $files = [];

        foreach ($locales as $locale) {
            $localPath = sprintf('%s/%s.pdf', sys_get_temp_dir(), uniqid('footprint-certificate-', true));
            file_put_contents($localPath, 'pdf-' . $locale->value);

            $files[$locale->value] = $this->storage->store($certificate->id(), $this->accountId, $locale, $localPath);

            @unlink($localPath);
        }

        $certificate->markGenerated($files);
        $this->certificates->add($certificate);

        return $certificate;
    }

    private function download(PropertyCarbonFootprintCertificate $certificate, Locale $locale, User $user): DownloadPropertyCarbonFootprintCertificateResponse
    {
        return (new DownloadPropertyCarbonFootprintCertificateService(
            $this->certificates,
            new PropertyCarbonFootprintCertificateAuthorizer(),
            $this->storage,
        ))(new DownloadPropertyCarbonFootprintCertificateRequest($certificate->id(), $locale), $user);
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->accountId);
    }
}
