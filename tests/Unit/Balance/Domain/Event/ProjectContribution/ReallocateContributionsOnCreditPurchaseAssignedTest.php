<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Event\ProjectContribution;

use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsRequest;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsService;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsService;
use App\Balance\Domain\Event\ProjectContribution\ReallocateContributionsOnCreditPurchaseValidatedSubscriber;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Application\ProjectContribution\FakeCreditReservationLock;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use PHPUnit\Framework\TestCase;

class ReallocateContributionsOnCreditPurchaseAssignedTest extends TestCase
{
    private AccountId $accountId;
    private ProjectId $projectId;
    private InMemoryProjectContributionRepository $contributions;
    private InMemoryCreditPurchaseRepository $purchases;
    private InMemoryProjectRepository $projects;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        $this->purchases = CreditPurchaseRepositoryMother::inMemory();
        $this->projects = ProjectRepositoryMother::inMemory();

        $this->projects->add(ProjectMother::contributable(
            projectType: ProjectType::EmissionCompensation,
            id: $this->projectId,
        ));
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->clearPending();
        DomainEventPublisher::instance()->reset();
    }

    public function testAnAssignedCreditAbsorbsTheUnderallocatedContributionsOfTheAccount(): void
    {
        $contribution = ProjectContributionMother::paid($this->accountId, $this->projectId, 5.0);
        $this->contributions->add($contribution);

        (new AllocateContributionCreditsService(
            $this->contributions,
            $this->purchases,
            $this->projects,
            new FakeCreditReservationLock(),
        ))(new AllocateContributionCreditsRequest($contribution->id()));

        $this->assertSame(5.0, $contribution->unallocatedTonnes());

        new ReallocateContributionsOnCreditPurchaseValidatedSubscriber(
            $this->purchases,
            new ReallocateUnderallocatedContributionsService(
                $this->contributions,
                $this->purchases,
                $this->projects,
                new FakeCreditReservationLock(),
            ),
        );

        DomainEventPublisher::instance()->lock();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->assign(
            accountId: $this->accountId,
            projectId: $this->projectId,
            purchasedTonnes: 10.0,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
            reference: 'ASSIGN-1',
            certificate: null,
            assignedBy: UserId::generate(),
        );
        $this->purchases->add($creditPurchase);

        DomainEventPublisher::instance()->unlock();

        $this->assertSame(5.0, $contribution->allocatedTonnes());
        $this->assertSame(0.0, $contribution->unallocatedTonnes());

        $allocations = $contribution->creditAllocations();
        $this->assertCount(1, $allocations);
        $this->assertTrue($creditPurchase->id()->equals($allocations[0]->creditPurchaseId()));
    }
}
