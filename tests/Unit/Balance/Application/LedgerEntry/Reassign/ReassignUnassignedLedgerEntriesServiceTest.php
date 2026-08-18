<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\LedgerEntry\Reassign;

use App\Balance\Application\LedgerEntry\Reassign\ReassignUnassignedLedgerEntriesService;
use App\Balance\Application\LedgerEntry\Record\RecordContributionLedgerEntriesRequest;
use App\Balance\Application\LedgerEntry\Record\RecordContributionLedgerEntriesService;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsRequest;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsService;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsRequest;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsService;
use App\Balance\Domain\Event\LedgerEntry\ReassignLedgerEntriesOnCreditsReallocatedSubscriber;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditsReallocated;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Application\ProjectContribution\FakeCreditReservationLock;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ReassignUnassignedLedgerEntriesServiceTest extends TestCase
{
    private AccountId $accountId;
    private ProjectId $projectId;
    private InMemoryProjectContributionRepository $contributions;
    private InMemoryCreditPurchaseRepository $purchases;
    private InMemoryProjectRepository $projects;
    private InMemoryLedgerEntryRepository $ledger;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        $this->purchases = CreditPurchaseRepositoryMother::inMemory();
        $this->projects = ProjectRepositoryMother::inMemory();
        $this->ledger = LedgerEntryRepositoryMother::inMemory();

        $this->projects->add(ProjectMother::contributable(projectType: ProjectType::EmissionCompensation, id: $this->projectId));
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testValidatingALotCoversTheUnassignedBalanceOfAPaidContribution(): void
    {
        $contribution = $this->givenPaidContributionWithoutCredit(2.0);

        $this->assertSame(30.0, $this->ledger->balanceFor(LedgerBeneficiary::Unassigned, null)->accruedAmount());

        $this->givenValidatedPurchase(2.0, CreditPurchasePaymentType::PaidByClient);
        $this->reallocate();

        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Unassigned, null)->accruedAmount());
        $this->assertSame(30.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->accruedAmount());
        $this->assertSame($contribution->totalAmount(), $this->ledger->recordedAmountFor($contribution->id()));
        $this->assertSame(2.0, $contribution->allocatedTonnes());
    }

    public function testALotPaidByBioscoreSendsTheReassignedAmountToBioscore(): void
    {
        $this->givenPaidContributionWithoutCredit(2.0);

        $this->givenValidatedPurchase(2.0, CreditPurchasePaymentType::PaidByBioscore);
        $this->reallocate();

        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Unassigned, null)->accruedAmount());
        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->accruedAmount());
        $this->assertSame(48.4, $this->ledger->balanceFor(LedgerBeneficiary::Bioscore, null)->accruedAmount());
    }

    public function testAPartialLotOnlyCoversWhatItCanAndLeavesTheRestUnassigned(): void
    {
        $this->givenPaidContributionWithoutCredit(2.0);

        $this->givenValidatedPurchase(1.0, CreditPurchasePaymentType::PaidByClient);
        $this->reallocate();

        $this->assertSame(15.0, $this->ledger->balanceFor(LedgerBeneficiary::Unassigned, null)->accruedAmount());
        $this->assertSame(15.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->accruedAmount());
    }

    public function testReallocatingTwiceDoesNotDuplicateTheReassignment(): void
    {
        $this->givenPaidContributionWithoutCredit(2.0);

        $this->givenValidatedPurchase(2.0, CreditPurchasePaymentType::PaidByClient);

        $this->reallocate();
        $this->reallocate();

        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Unassigned, null)->accruedAmount());
        $this->assertSame(30.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->accruedAmount());
    }

    public function testTheReallocationIsAnnouncedAsADomainEvent(): void
    {
        $this->givenPaidContributionWithoutCredit(2.0);
        $this->givenValidatedPurchase(2.0, CreditPurchasePaymentType::PaidByClient);

        $spy = new SpyDomainEventSubscriber();

        $this->reallocate();

        $reallocated = array_filter(
            $spy->events(),
            static fn(object $event): bool => $event instanceof ProjectContributionCreditsReallocated,
        );

        $this->assertCount(1, $reallocated);
    }

    public function testAContributionAlreadyFullyAllocatedIsLeftAlone(): void
    {
        $this->givenValidatedPurchase(2.0, CreditPurchasePaymentType::PaidByClient);

        $contribution = ProjectContributionMother::paid($this->accountId, $this->projectId, 2.0);
        $this->contributions->add($contribution);

        $this->allocate();
        $this->record($contribution);

        $before = $this->ledger->totalResultsByFilter(new LedgerEntryFilter(
            projectContributionId: $contribution->id(),
        ));

        $this->reallocate();

        $after = $this->ledger->totalResultsByFilter(new LedgerEntryFilter(
            projectContributionId: $contribution->id(),
        ));

        $this->assertSame($before, $after);
    }

    private function givenPaidContributionWithoutCredit(float $tonnes): ProjectContribution
    {
        $contribution = ProjectContributionMother::paid($this->accountId, $this->projectId, $tonnes);

        $this->contributions->add($contribution);

        $this->record($contribution);

        return $contribution;
    }

    private function givenValidatedPurchase(float $tonnes, CreditPurchasePaymentType $paymentType): CreditPurchase
    {
        $purchase = CreditPurchaseMother::validatedAt(
            new \DateTimeImmutable('-1 day'),
            $this->accountId,
            $this->projectId,
            $tonnes,
            $paymentType,
        );

        $this->purchases->add($purchase);

        return $purchase;
    }

    private function record(ProjectContribution $contribution): void
    {
        (new RecordContributionLedgerEntriesService(
            $this->contributions,
            $this->purchases,
            $this->projects,
            $this->ledger,
        ))(new RecordContributionLedgerEntriesRequest($contribution->id()));
    }

    private function allocate(): void
    {
        (new AllocateContributionCreditsService(
            $this->contributions,
            $this->purchases,
            $this->projects,
            new FakeCreditReservationLock(),
        ))(new AllocateContributionCreditsRequest(
            array_values($this->contributions->findByFilter(new ProjectContributionFilter(limit: 0)))[0]->id(),
        ));
    }

    private function reallocate(): void
    {
        new ReassignLedgerEntriesOnCreditsReallocatedSubscriber(
            new ReassignUnassignedLedgerEntriesService($this->ledger, $this->contributions, $this->purchases),
        );

        (new ReallocateUnderallocatedContributionsService(
            $this->contributions,
            $this->purchases,
            $this->projects,
            new FakeCreditReservationLock(),
        ))(new ReallocateUnderallocatedContributionsRequest($this->accountId, $this->projectId));
    }
}
