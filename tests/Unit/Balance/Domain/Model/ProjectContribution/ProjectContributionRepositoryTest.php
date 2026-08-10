<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ProjectContributionRepositoryTest extends KernelTestCase
{
    public function testAddProjectContributionToRepository(): void
    {
        $repository = $this->createSUT();

        $projectContribution = ProjectContributionMother::random();

        $this->assertNull($repository->find($projectContribution->id()));

        $repository->add($projectContribution);

        $recovered = $repository->find($projectContribution->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($projectContribution->id()->equals($recovered->id()));
    }

    public function testFindOneByPaymentId(): void
    {
        $repository = $this->createSUT();

        $paymentId = PaymentId::generate();

        $repository->add(ProjectContributionMother::pending(paymentId: $paymentId));
        $repository->add(ProjectContributionMother::random());

        $found = $repository->findOneByPaymentId($paymentId);

        $this->assertNotNull($found);
        $this->assertNotNull($found->paymentId());
        $this->assertTrue($paymentId->equals($found->paymentId()));

        $this->assertNull($repository->findOneByPaymentId(PaymentId::generate()));
    }

    public function testFindByFilterByAccountId(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(ProjectContributionMother::pending($accountId));
        $repository->add(ProjectContributionMother::paid($accountId));
        $repository->add(ProjectContributionMother::random());

        $result = $repository->findByFilter(new ProjectContributionFilter(accountId: $accountId, limit: 0));

        $this->assertCount(2, $result);
    }

    public function testFindByFilterByProjectIdAndStatus(): void
    {
        $repository = $this->createSUT();

        $projectId = ProjectId::generate();

        $repository->add(ProjectContributionMother::paid(projectId: $projectId));
        $repository->add(ProjectContributionMother::pending(projectId: $projectId));
        $repository->add(ProjectContributionMother::paid());

        $result = $repository->findByFilter(new ProjectContributionFilter(
            projectId: $projectId,
            status: ProjectContributionStatus::Paid,
            limit: 0,
        ));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByExpiresBeforeOnlyReturnsOverdueReservations(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProjectContributionMother::overdue());
        $repository->add(ProjectContributionMother::pending());

        $result = $repository->findByFilter(new ProjectContributionFilter(
            status: ProjectContributionStatus::Pending,
            expiresBefore: new \DateTimeImmutable('NOW'),
            limit: 0,
        ));

        $this->assertCount(1, $result);
    }

    public function testTotalResultsIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(ProjectContributionMother::pending($accountId));
        $repository->add(ProjectContributionMother::pending($accountId));
        $repository->add(ProjectContributionMother::pending($accountId));

        $filter = new ProjectContributionFilter(accountId: $accountId, limit: 2);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));
    }

    public function testCommittedTonnesAddsPaidAndLiveReservations(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();
        $now = new \DateTimeImmutable('NOW');

        $repository->add(ProjectContributionMother::paid($accountId, $projectId, tonnes: 3.0));
        $repository->add(ProjectContributionMother::pending($accountId, $projectId, tonnes: 2.0));
        $repository->add(ProjectContributionMother::overdue($accountId, $projectId, tonnes: 4.0));
        $repository->add(ProjectContributionMother::expired($accountId, $projectId, tonnes: 5.0));

        $this->assertSame(5.0, $repository->committedTonnes($accountId, $projectId, $now));
        $this->assertSame(2.0, $repository->reservedTonnes($accountId, $projectId, $now));
    }

    public function testCommittedTonnesByAccountIsGroupedByProject(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $firstProjectId = ProjectId::generate();
        $secondProjectId = ProjectId::generate();

        $repository->add(ProjectContributionMother::paid($accountId, $firstProjectId, tonnes: 1.5));
        $repository->add(ProjectContributionMother::paid($accountId, $firstProjectId, tonnes: 2.5));
        $repository->add(ProjectContributionMother::paid($accountId, $secondProjectId, tonnes: 1.0));
        $repository->add(ProjectContributionMother::paid(projectId: $firstProjectId, tonnes: 9.0));

        $committed = $repository->committedTonnesByAccount($accountId, new \DateTimeImmutable('NOW'));

        $this->assertCount(2, $committed);
        $this->assertSame(4.0, $committed[(string) $firstProjectId]);
        $this->assertSame(1.0, $committed[(string) $secondProjectId]);
    }

    public function testCommittedTonnesOfAnUnknownProjectIsZero(): void
    {
        $repository = $this->createSUT();

        $committed = $repository->committedTonnes(
            AccountId::generate(),
            ProjectId::generate(),
            new \DateTimeImmutable('NOW'),
        );

        $this->assertSame(0.0, $committed);
    }

    abstract protected function createSUT(): ProjectContributionRepository;
}
