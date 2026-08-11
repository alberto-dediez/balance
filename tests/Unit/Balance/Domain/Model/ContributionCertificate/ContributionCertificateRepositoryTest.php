<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateFilter;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateStatus;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ContributionCertificateRepositoryTest extends KernelTestCase
{
    public function testAddContributionCertificateToRepository(): void
    {
        $repository = $this->createSUT();

        $certificate = ContributionCertificateMother::random();

        $this->assertNull($repository->find($certificate->id()));

        $repository->add($certificate);

        $recovered = $repository->find($certificate->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($certificate->id()->equals($recovered->id()));
    }

    public function testFindByCode(): void
    {
        $repository = $this->createSUT();

        $repository->add(ContributionCertificateMother::issued(code: 'AAAA-BBBB-CCCC'));
        $repository->add(ContributionCertificateMother::issued(code: 'DDDD-EEEE-FFFF'));

        $found = $repository->findByCode('DDDD-EEEE-FFFF');

        $this->assertNotNull($found);
        $this->assertSame('DDDD-EEEE-FFFF', $found->code());
        $this->assertNull($repository->findByCode('ZZZZ-ZZZZ-ZZZZ'));
    }

    public function testFindByVerificationToken(): void
    {
        $repository = $this->createSUT();

        $certificate = ContributionCertificateMother::random();

        $repository->add($certificate);
        $repository->add(ContributionCertificateMother::random());

        $found = $repository->findByVerificationToken($certificate->verificationToken());

        $this->assertNotNull($found);
        $this->assertTrue($certificate->id()->equals($found->id()));
        $this->assertNull($repository->findByVerificationToken('not-a-token'));
    }

    /** The reference printed on the document must not be a way into the verification page. */
    public function testTheCodeIsNotAVerificationToken(): void
    {
        $repository = $this->createSUT();

        $certificate = ContributionCertificateMother::issued(code: 'AAAA-BBBB-CCCC');

        $repository->add($certificate);

        $this->assertNull($repository->findByVerificationToken('AAAA-BBBB-CCCC'));
    }

    public function testFindByProjectContributionId(): void
    {
        $repository = $this->createSUT();

        $projectContributionId = ProjectContributionId::generate();

        $repository->add(ContributionCertificateMother::issued(
            projectContributionId: $projectContributionId,
            code: ContributionCertificateMother::randomCode(),
        ));
        $repository->add(ContributionCertificateMother::random());

        $found = $repository->findByProjectContributionId($projectContributionId);

        $this->assertNotNull($found);
        $this->assertNotNull($found->projectContributionId());
        $this->assertTrue($projectContributionId->equals($found->projectContributionId()));

        $this->assertNull($repository->findByProjectContributionId(ProjectContributionId::generate()));
    }

    public function testFilterByAccountTypeAndStatus(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(ContributionCertificateMother::issued(
            accountId: $accountId,
            code: ContributionCertificateMother::randomCode(),
        ));
        $repository->add(ContributionCertificateMother::issued(
            type: ContributionCertificateType::SocialContribution,
            accountId: $accountId,
            code: ContributionCertificateMother::randomCode(),
        ));
        $repository->add(ContributionCertificateMother::random());

        $this->assertCount(2, $repository->findByFilter(new ContributionCertificateFilter(accountId: $accountId, limit: 0)));
        $this->assertSame(2, $repository->totalResultsByFilter(new ContributionCertificateFilter(accountId: $accountId, limit: 0)));

        $compensations = $repository->findByFilter(new ContributionCertificateFilter(
            accountId: $accountId,
            type: ContributionCertificateType::Compensation,
            limit: 0,
        ));
        $this->assertCount(1, $compensations);

        $this->assertCount(0, $repository->findByFilter(new ContributionCertificateFilter(
            accountId: $accountId,
            status: ContributionCertificateStatus::Generated,
            limit: 0,
        )));
    }

    abstract protected function createSUT(): ContributionCertificateRepository;
}
