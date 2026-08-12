<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate\Issue;

use App\Balance\Application\ContributionCertificate\Issue\IssueContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Issue\IssueContributionCertificateResponse;
use App\Balance\Application\ContributionCertificate\Issue\IssueContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContributionCertificateRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class IssueContributionCertificateServiceTest extends TestCase
{
    private AccountId $accountId;
    private ProjectId $projectId;
    private InMemoryContributionCertificateRepository $certificates;
    private InMemoryProjectContributionRepository $contributions;
    private InMemoryProjectRepository $projects;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->certificates = ContributionCertificateRepositoryMother::inMemory();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        $this->projects = ProjectRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testACompensationProjectIssuesACompensationCertificate(): void
    {
        $this->givenProject(ProjectType::EmissionCompensation);
        $contribution = $this->givenPaidContribution();

        $response = $this->issue($contribution->id());

        $this->assertTrue($response->issued);
        $this->assertSame(ContributionCertificateType::Compensation, $response->contributionCertificate->type());
        $this->assertNotNull($response->contributionCertificate->co2eqKg());
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $response->contributionCertificate->code());
    }

    public function testTheVerificationTokenIsIndependentOfThePrintedCode(): void
    {
        $this->givenProject(ProjectType::EmissionCompensation);
        $contribution = $this->givenPaidContribution();

        $certificate = $this->issue($contribution->id())->contributionCertificate;

        $this->assertNotSame('', $certificate->verificationToken());
        $this->assertNotSame($certificate->code(), $certificate->verificationToken());
    }

    public function testASocialProjectIssuesADocumentWithoutEmissions(): void
    {
        $this->givenProject(ProjectType::SocialProject);
        $contribution = $this->givenPaidContribution();

        $certificate = $this->issue($contribution->id())->contributionCertificate;

        $this->assertSame(ContributionCertificateType::SocialContribution, $certificate->type());
        $this->assertNull($certificate->co2eqKg());
        $this->assertSame([], $certificate->masterSerials());
    }

    public function testTheSerialsOfTheConsumedLotsAreCopied(): void
    {
        $this->givenProject(ProjectType::EmissionCompensation);

        $contribution = $this->givenPaidContribution();
        $contribution->allocateCredits([
            new AllocatedCreditLine(0, CreditPurchaseId::generate(), '2024-b012', 0.6),
            new AllocatedCreditLine(1, CreditPurchaseId::generate(), '2024-b013', 0.4),
        ]);

        $certificate = $this->issue($contribution->id())->contributionCertificate;

        $this->assertSame(['2024-b012', '2024-b013'], $certificate->masterSerials());
    }

    public function testTheFlowTheContributionCameFromIsCopied(): void
    {
        $this->givenProject(ProjectType::EmissionCompensation);
        $contribution = $this->givenPaidContribution();

        $certificate = $this->issue($contribution->id())->contributionCertificate;

        $this->assertSame($contribution->origin(), $certificate->origin());
        $this->assertSame($contribution->originName(), $certificate->originName());
    }

    public function testACompanyBecomesTheBeneficiaryAndThePersonTheContact(): void
    {
        $this->givenProject(ProjectType::EmissionCompensation);
        $contribution = $this->givenPaidContribution(isCompany: true);

        $certificate = $this->issue($contribution->id())->contributionCertificate;

        $this->assertTrue($certificate->beneficiaryIsCompany());
        $this->assertSame('Acme SL', $certificate->beneficiaryName());
        $this->assertSame('B12345678', $certificate->beneficiaryVatNumber());
        $this->assertSame($contribution->contributorName(), $certificate->contactName());
    }

    public function testIssuingTwiceReturnsTheSameCertificate(): void
    {
        $this->givenProject(ProjectType::EmissionCompensation);
        $contribution = $this->givenPaidContribution();

        $first = $this->issue($contribution->id());
        $second = $this->issue($contribution->id());

        $this->assertTrue($first->issued);
        $this->assertFalse($second->issued);
        $this->assertSame($first->contributionCertificate->code(), $second->contributionCertificate->code());
    }

    public function testTheCertificateIsIssuedInTheLanguageOfTheContribution(): void
    {
        $this->givenProject(ProjectType::EmissionCompensation);

        $contribution = ProjectContributionMother::paid($this->accountId, $this->projectId, locale: Locale::English);
        $this->contributions->add($contribution);

        LocaleGetter::setLocale(Locale::Spanish);

        try {
            $certificate = $this->issue($contribution->id())->contributionCertificate;
        } finally {
            LocaleGetter::setLocale(null);
        }

        $this->assertSame(Locale::English, $certificate->locale());
    }

    public function testAnUnpaidContributionIsRejected(): void
    {
        $this->givenProject(ProjectType::EmissionCompensation);

        $contribution = ProjectContributionMother::pending($this->accountId, $this->projectId);
        $this->contributions->add($contribution);

        $this->expectException(\InvalidArgumentException::class);

        $this->issue($contribution->id());
    }

    private function givenProject(ProjectType $projectType): void
    {
        $this->projects->add(ProjectMother::contributable(projectType: $projectType, id: $this->projectId));
    }

    private function givenPaidContribution(bool $isCompany = false): ProjectContribution
    {
        $contribution = $isCompany
            ? ProjectContributionMother::paidCompany($this->accountId, $this->projectId)
            : ProjectContributionMother::paid($this->accountId, $this->projectId);

        $this->contributions->add($contribution);

        return $contribution;
    }

    private function issue(ProjectContributionId $projectContributionId): IssueContributionCertificateResponse
    {
        return (new IssueContributionCertificateService(
            $this->certificates,
            $this->contributions,
            $this->projects,
            new InMemoryDestinationRepository(),
            new FakeContributionCertificateCodeGenerator(),
            new FakeContributionCertificateVerificationTokenGenerator(),
        ))(new IssueContributionCertificateRequest($projectContributionId), UserMother::systemUser());
    }
}
