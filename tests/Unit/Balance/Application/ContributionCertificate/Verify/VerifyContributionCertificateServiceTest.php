<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate\Verify;

use App\Balance\Application\ContributionCertificate\Verify\VerifyContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Verify\VerifyContributionCertificateResponse;
use App\Balance\Application\ContributionCertificate\Verify\VerifyContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContributionCertificateRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateMother;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepositoryMother;
use PHPUnit\Framework\TestCase;

class VerifyContributionCertificateServiceTest extends TestCase
{
    private InMemoryContributionCertificateRepository $certificates;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->certificates = ContributionCertificateRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAGeneratedCertificateIsReportedValid(): void
    {
        $certificate = ContributionCertificateMother::generated();
        $this->certificates->add($certificate);

        $response = $this->verify($certificate->verificationToken());

        $this->assertNotNull($response);
        $this->assertTrue($response->valid);
        $this->assertSame('Reforestación', $response->projectName);
        $this->assertSame(['2024-b012'], $response->masterSerials);
        $this->assertSame(1000.0, $response->co2eqKg);
    }

    public function testTheResponseCarriesTheBeneficiaryButNoOtherPersonalData(): void
    {
        $certificate = ContributionCertificateMother::generated();
        $this->certificates->add($certificate);

        $response = $this->verify($certificate->verificationToken());

        $this->assertNotNull($response);
        $this->assertSame('Guest', $response->beneficiaryName);

        $fields = array_keys(get_object_vars($response));

        foreach (['beneficiaryVatNumber', 'contactName', 'contactEmail', 'contributionAmount', 'contributorCountry'] as $personalField) {
            $this->assertNotContains($personalField, $fields);
        }
    }

    public function testTheLotsAreExposedWithTheirTonneRange(): void
    {
        $certificate = ContributionCertificateMother::generated();
        $this->certificates->add($certificate);

        $response = $this->verify($certificate->verificationToken());

        $this->assertNotNull($response);
        $this->assertSame(
            [['serial' => '2024-b012', 'startTonnes' => 0.0, 'endTonnes' => 1.0]],
            $response->creditLots,
        );
    }

    public function testASocialCertificateReportsNoEmissions(): void
    {
        $certificate = ContributionCertificateMother::generated(type: ContributionCertificateType::SocialContribution);
        $this->certificates->add($certificate);

        $response = $this->verify($certificate->verificationToken());

        $this->assertNotNull($response);
        $this->assertNull($response->co2eqKg);
        $this->assertSame([], $response->masterSerials);
        $this->assertSame([], $response->creditLots);
    }

    public function testAnUnknownTokenReturnsNothing(): void
    {
        $this->assertNull($this->verify('not-a-token'));
        $this->assertNull($this->verify('  '));
    }

    public function testThePrintedCodeDoesNotVerifyTheCertificate(): void
    {
        $certificate = ContributionCertificateMother::generated();
        $this->certificates->add($certificate);

        $this->assertNull($this->verify($certificate->code()));
    }

    public function testTheResponseCarriesTheReferencePrintedOnTheDocument(): void
    {
        $certificate = ContributionCertificateMother::generated();
        $this->certificates->add($certificate);

        $response = $this->verify($certificate->verificationToken());

        $this->assertNotNull($response);
        $this->assertSame($certificate->code(), $response->code);
    }

    private function verify(string $verificationToken): ?VerifyContributionCertificateResponse
    {
        return (new VerifyContributionCertificateService($this->certificates))(
            new VerifyContributionCertificateRequest($verificationToken),
        );
    }
}
