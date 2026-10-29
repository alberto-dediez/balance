<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Transfer;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Application\AccountProject\Select\SelectProjectService;
use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditPurchase\Transfer\TransferableCredit;
use App\Balance\Application\CreditPurchase\Transfer\TransferCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Transfer\TransferCreditPurchaseResponse;
use App\Balance\Application\CreditPurchase\Transfer\TransferCreditPurchaseService;
use App\Balance\Application\CreditPurchase\Transfer\TransferredLot;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsRequest;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsService;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsService;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseReceived;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseTransferred;
use App\Balance\Domain\Model\CreditPurchase\InsufficientTransferableCreditException;
use App\Balance\Domain\Model\CreditPurchase\NonTransferableCreditPurchaseException;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountProjectRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Application\ProjectContribution\FakeCreditReservationLock;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class TransferCreditPurchaseServiceTest extends TestCase
{
    private AccountId $sourceAccountId;
    private AccountId $destinationAccountId;
    private ProjectId $projectId;
    private InMemoryCreditPurchaseRepository $purchases;
    private InMemoryProjectContributionRepository $contributions;
    private InMemoryProjectRepository $projects;
    private InMemoryAccountProjectRepository $accountProjects;
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->sourceAccountId = AccountId::generate();
        $this->destinationAccountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->purchases = CreditPurchaseRepositoryMother::inMemory();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        $this->projects = ProjectRepositoryMother::inMemory();
        $this->accountProjects = AccountProjectRepositoryMother::inMemory();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->projects->add(ProjectMother::contributable(
            projectType: ProjectType::EmissionCompensation,
            id: $this->projectId,
        ));

        $this->accountProjects->add(AccountProjectMother::from($this->sourceAccountId, $this->projectId));
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testTheRemainderIsVoidedAtTheSourceAndOpensANewLotAtTheDestination(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->sourceAccountId, 30.0, allocate: true);

        $response = $this->transfer();

        $this->assertSame(70.0, $response->transferredTonnes);
        $this->assertSame(700.0, $response->transferredAmount);

        $this->assertSame(30.0, $source->availableTonnes());
        $this->assertSame(300.0, $source->amount());
        $this->assertSame(10.0, $source->pricePerTonne());

        $destination = $this->purchases->find($response->lots[0]->destinationCreditPurchaseId);

        $this->assertInstanceOf(CreditPurchase::class, $destination);
        $this->assertNotNull($destination->accountId());
        $this->assertTrue($this->destinationAccountId->equals($destination->accountId()));
        $this->assertSame(70.0, $destination->availableTonnes());
        $this->assertSame(30.0, $destination->offsetTonnes());
        $this->assertSame(700.0, $destination->amount());
        $this->assertSame(10.0, $destination->pricePerTonne());
        $this->assertSame(2, $destination->lotNumber());
        $this->assertSame($source->reference(), $destination->reference());

        $this->assertCount(1, $this->eventsOfType(CreditPurchaseTransferred::class));
        $this->assertCount(1, $this->eventsOfType(CreditPurchaseReceived::class));
    }

    public function testAPartialTransferLeavesTheRestUsableAtTheSource(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);

        $response = $this->transfer(tonnes: 40.0);

        $this->assertSame(40.0, $response->transferredTonnes);
        $this->assertSame(60.0, $source->availableTonnes());
        $this->assertSame(600.0, $source->amount());

        $destination = $this->purchases->find($response->lots[0]->destinationCreditPurchaseId);

        $this->assertInstanceOf(CreditPurchase::class, $destination);
        $this->assertSame(60.0, $destination->offsetTonnes());
        $this->assertSame(40.0, $destination->availableTonnes());
        $this->assertSame(400.0, $destination->amount());
    }

    public function testAChainedTransferKeepsTheRangesContiguous(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->sourceAccountId, 30.0, allocate: true);

        $first = $this->transfer();

        $second = $this->purchases->find($first->lots[0]->destinationCreditPurchaseId);

        $this->assertInstanceOf(CreditPurchase::class, $second);

        $this->givenPaidContribution($this->destinationAccountId, 20.0, allocate: true);

        $thirdAccountId = AccountId::generate();

        $response = $this->transfer(
            creditPurchaseId: $second->id(),
            destinationAccountIds: [$thirdAccountId],
            tonnes: 40.0,
        );

        $third = $this->purchases->find($response->lots[0]->destinationCreditPurchaseId);

        $this->assertInstanceOf(CreditPurchase::class, $third);
        $this->assertSame(30.0, $source->availableTonnes());
        $this->assertSame(30.0, $second->availableTonnes());
        $this->assertSame(40.0, $second->voidedTonnes());
        $this->assertSame(60.0, $third->offsetTonnes());
        $this->assertSame(40.0, $third->availableTonnes());
        $this->assertSame(3, $third->lotNumber());
        $this->assertSame($source->reference(), $third->reference());
    }

    public function testTonnesAboveTheRemainderAreTrimmedSilently(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->sourceAccountId, 30.0, allocate: true);

        $response = $this->transfer(tonnes: 500.0);

        $this->assertSame(70.0, $response->transferredTonnes);
        $this->assertSame(30.0, $source->availableTonnes());
    }

    public function testAnExhaustedLotCannotBeTransferred(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->sourceAccountId, 100.0, allocate: true);

        $this->expectException(InsufficientTransferableCreditException::class);

        $this->transfer();
    }

    public function testAPendingContributionCapsTheTransferableRemainder(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);
        $this->contributions->add(ProjectContributionMother::pending(
            $this->sourceAccountId,
            $this->projectId,
            25.0,
            expiresAt: new \DateTimeImmutable('+12 hours'),
        ));

        $response = $this->transfer();

        $this->assertSame(75.0, $response->transferredTonnes);
        $this->assertSame(25.0, $source->availableTonnes());
        $this->assertSame(
            0.0,
            $this->creditAvailability()->for($this->sourceAccountId, $this->projectId)->availableTonnes(),
        );
    }

    public function testTheDestinationIsSubscribedToTheProject(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);

        $this->assertCount(0, $this->accountProjects->findByFilter(
            new AccountProjectFilter(accountId: $this->destinationAccountId, projectId: $this->projectId, limit: 0),
        ));

        $this->transfer();

        $this->assertCount(1, $this->accountProjects->findByFilter(
            new AccountProjectFilter(accountId: $this->destinationAccountId, projectId: $this->projectId, limit: 0),
        ));
    }

    public function testTheTransferredCreditIsAbsorbedByUnderallocatedContributions(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->destinationAccountId, 15.0, allocate: true);

        $response = $this->transfer();

        $this->assertSame(15.0, $response->absorbedTonnes);
    }

    public function testAPurchaseWithoutAmountIsTransferredWithoutProrating(): void
    {
        $source = $this->givenBioscorePurchase(100.0, null);

        $response = $this->transfer();

        $this->assertSame(100.0, $response->transferredTonnes);
        $this->assertNull($response->transferredAmount);
        $this->assertNull($source->amount());

        $destination = $this->purchases->find($response->lots[0]->destinationCreditPurchaseId);

        $this->assertInstanceOf(CreditPurchase::class, $destination);
        $this->assertNull($destination->amount());
    }

    public function testAPurchasePaidByTheClientIsNotTransferable(): void
    {
        $source = CreditPurchaseMother::validated($this->sourceAccountId, $this->projectId, 100.0);
        $this->purchases->add($source);

        $this->expectException(NonTransferableCreditPurchaseException::class);

        $this->transfer($source->id());
    }

    public function testTheDestinationCannotBeTheSourceAccount(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);

        $this->expectException(NonTransferableCreditPurchaseException::class);

        $this->transfer(destinationAccountIds: [$this->sourceAccountId]);
    }

    public function testTwoSuccessiveTransfersFromTheSameLotGiveEachDestinationOnlyWhatWasTransferred(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);
        $secondAccountId = AccountId::generate();

        $first = $this->lot($this->transfer(tonnes: 40.0), 0);
        $second = $this->lot($this->transfer(destinationAccountIds: [$secondAccountId], tonnes: 20.0), 0);

        $this->assertSame(60.0, $first->offsetTonnes());
        $this->assertSame(0.0, $first->upperOffsetTonnes());
        $this->assertSame(40.0, $first->availableTonnes());

        $this->assertSame(40.0, $second->offsetTonnes());
        $this->assertSame(40.0, $second->upperOffsetTonnes());
        $this->assertSame(20.0, $second->availableTonnes());
        $this->assertSame(20.0, $second->requestedTonnes());
        $this->assertSame(100.0, $second->purchasedTonnes());
        $this->assertFalse($second->isTransferred());
    }

    public function testTheWholeRemainderIsSplitEquallyAndTheLastAccountTakesTheRemainder(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->sourceAccountId, 30.0, allocate: true);
        $accountIds = [AccountId::generate(), AccountId::generate(), AccountId::generate()];

        $response = $this->transfer(destinationAccountIds: $accountIds);

        $this->assertCount(3, $response->lots);
        $this->assertSame(70.0, $response->transferredTonnes);
        $this->assertSame(23.333333, $response->tonnesPerAccount);
        $this->assertSame(0.0, $response->retainedTonnes);
        $this->assertSame(0.000001, $response->lastAccountExtraTonnes);
        $this->assertSame([23.333333, 23.333333, 23.333334], array_map(
            static fn(TransferredLot $lot): float => $lot->tonnes,
            $response->lots,
        ));

        $expectedRanges = [
            [76.666667, 0.0, 23.333333, 2],
            [53.333334, 23.333333, 23.333333, 3],
            [30.0, 46.666666, 23.333334, 4],
        ];

        foreach ($expectedRanges as $index => [$offset, $upperOffset, $available, $lotNumber]) {
            $lot = $this->lot($response, $index);

            $this->assertNotNull($lot->accountId());
            $this->assertTrue($accountIds[$index]->equals($lot->accountId()));
            $this->assertSame($offset, $lot->offsetTonnes());
            $this->assertSame($upperOffset, $lot->upperOffsetTonnes());
            $this->assertEqualsWithDelta($available, $lot->availableTonnes(), 1e-9);
            $this->assertSame($lotNumber, $lot->lotNumber());
        }

        $this->assertEqualsWithDelta(30.0, $source->availableTonnes(), 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->transferableCredit()->forPurchase($source), 1e-9);
        $this->assertCount(3, $this->eventsOfType(CreditPurchaseReceived::class));
    }

    public function testAPartialSplitKeepsTheIndivisibleRemainderAtTheSource(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->sourceAccountId, 30.0, allocate: true);

        $response = $this->transfer(
            destinationAccountIds: [AccountId::generate(), AccountId::generate(), AccountId::generate()],
            tonnes: 10.0,
        );

        $this->assertSame(3.333333, $response->tonnesPerAccount);
        $this->assertSame(9.999999, $response->transferredTonnes);
        $this->assertSame(0.000001, $response->retainedTonnes);
        $this->assertSame(0.0, $response->lastAccountExtraTonnes);

        foreach ($response->lots as $lot) {
            $this->assertSame(3.333333, $lot->tonnes);
        }

        $this->assertEqualsWithDelta(60.000001, $this->transferableCredit()->forPurchase($source), 1e-9);
    }

    public function testRequestedTonnesThatDivideExactlyLeaveNoRemainder(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);

        $response = $this->transfer(
            destinationAccountIds: [AccountId::generate(), AccountId::generate(), AccountId::generate(), AccountId::generate()],
            tonnes: 40.0,
        );

        $this->assertSame(40.0, $response->transferredTonnes);
        $this->assertSame(10.0, $response->tonnesPerAccount);
        $this->assertSame(0.0, $response->retainedTonnes);
        $this->assertSame(0.0, $response->lastAccountExtraTonnes);

        foreach ($response->lots as $lot) {
            $this->assertSame(10.0, $lot->tonnes);
        }

        $this->assertEqualsWithDelta(60.0, $source->availableTonnes(), 1e-9);
    }

    public function testRequestedTonnesAboveTheTransferableAreTrimmedAndTheRemainderGoesToTheLastAccount(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->sourceAccountId, 30.0, allocate: true);

        $response = $this->transfer(
            destinationAccountIds: [AccountId::generate(), AccountId::generate(), AccountId::generate()],
            tonnes: 500.0,
        );

        $this->assertSame(70.0, $response->transferredTonnes);
        $this->assertSame(0.0, $response->retainedTonnes);
        $this->assertSame(0.000001, $response->lastAccountExtraTonnes);
        $this->assertSame(23.333334, $response->lots[2]->tonnes);
        $this->assertEqualsWithDelta(0.0, $this->transferableCredit()->forPurchase($source), 1e-9);
    }

    public function testASingleDestinationReceivesExactlyTheTransferableRemainder(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->sourceAccountId, 30.0, allocate: true);

        $response = $this->transfer();

        $this->assertCount(1, $response->lots);
        $this->assertSame(70.0, $response->tonnesPerAccount);
        $this->assertSame(70.0, $response->lots[0]->tonnes);
        $this->assertSame(0.0, $response->retainedTonnes);
        $this->assertSame(0.0, $response->lastAccountExtraTonnes);
        $this->assertSame(70.0, $this->lot($response, 0)->availableTonnes());
    }

    public function testThePricePerTonneIsKeptAcrossDestinations(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);
        $this->givenPaidContribution($this->sourceAccountId, 30.0, allocate: true);

        $response = $this->transfer(
            destinationAccountIds: [AccountId::generate(), AccountId::generate(), AccountId::generate()],
        );

        $this->assertSame([233.33, 233.33, 233.34], array_map(
            static fn(TransferredLot $lot): ?float => $lot->amount,
            $response->lots,
        ));
        $this->assertSame(700.0, $response->transferredAmount);
        $this->assertSame(300.0, $source->amount());

        foreach ($response->lots as $index => $lot) {
            $this->assertSame($lot->amount, $this->lot($response, $index)->amount());
        }
    }

    public function testTheAmountIsSplitInCentsAndNeverExceedsTheSourceAmount(): void
    {
        $source = $this->givenBioscorePurchase(3.0, 200.0);

        $response = $this->transfer(
            destinationAccountIds: [AccountId::generate(), AccountId::generate(), AccountId::generate()],
        );

        $this->assertSame([66.66, 66.66, 66.68], array_map(
            static fn(TransferredLot $lot): ?float => $lot->amount,
            $response->lots,
        ));
        $this->assertSame(200.0, $response->transferredAmount);
        $this->assertSame(0.0, $source->amount());
    }

    public function testAPartialSplitKeepsTheIndivisibleCentsAtTheSource(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);

        $response = $this->transfer(
            destinationAccountIds: [AccountId::generate(), AccountId::generate(), AccountId::generate()],
            tonnes: 10.0,
        );

        $this->assertSame([33.33, 33.33, 33.33], array_map(
            static fn(TransferredLot $lot): ?float => $lot->amount,
            $response->lots,
        ));
        $this->assertSame(99.99, $response->transferredAmount);
        $this->assertSame(900.01, $source->amount());
    }

    public function testTheSourceAccountAmongTheDestinationsCancelsTheWholeSplit(): void
    {
        $source = $this->givenBioscorePurchase(100.0, 1000.0);

        try {
            $this->transfer(destinationAccountIds: [$this->destinationAccountId, $this->sourceAccountId]);

            $this->fail('The transfer should have been rejected.');
        } catch (NonTransferableCreditPurchaseException) {
        }

        $this->assertCount(1, $this->purchases->findByFilter(new CreditPurchaseFilter(limit: 0)));
        $this->assertCount(0, $this->eventsOfType(CreditPurchaseReceived::class));
        $this->assertSame(100.0, $source->availableTonnes());
    }

    public function testRepeatedDestinationsReceiveASingleLot(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);
        $otherAccountId = AccountId::generate();

        $response = $this->transfer(destinationAccountIds: [
            $this->destinationAccountId,
            new AccountId((string) $this->destinationAccountId),
            $otherAccountId,
        ]);

        $this->assertCount(2, $response->lots);
        $this->assertSame(50.0, $response->tonnesPerAccount);
        $this->assertTrue($this->destinationAccountId->equals($response->lots[0]->destinationAccountId));
        $this->assertTrue($otherAccountId->equals($response->lots[1]->destinationAccountId));
    }

    public function testAnEmptyDestinationListIsNotTransferable(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);

        $this->expectException(NonTransferableCreditPurchaseException::class);

        $this->transfer(destinationAccountIds: []);
    }

    public function testAShareBelowTheMinimumIsRejected(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);

        $this->expectException(InsufficientTransferableCreditException::class);

        $this->transfer(
            destinationAccountIds: [AccountId::generate(), AccountId::generate(), AccountId::generate()],
            tonnes: 0.000002,
        );
    }

    public function testTheAbsorbedTonnesAreReportedPerDestinationAndInTotal(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);
        $otherAccountId = AccountId::generate();
        $this->givenPaidContribution($this->destinationAccountId, 15.0, allocate: true);
        $this->givenPaidContribution($otherAccountId, 5.0, allocate: true);

        $response = $this->transfer(destinationAccountIds: [$this->destinationAccountId, $otherAccountId]);

        $this->assertSame(15.0, $response->lots[0]->absorbedTonnes);
        $this->assertSame(5.0, $response->lots[1]->absorbedTonnes);
        $this->assertSame(20.0, $response->absorbedTonnes);
    }

    public function testEveryDestinationIsSubscribedToTheProject(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);
        $otherAccountId = AccountId::generate();

        $this->transfer(destinationAccountIds: [$this->destinationAccountId, $otherAccountId]);

        foreach ([$this->destinationAccountId, $otherAccountId] as $accountId) {
            $this->assertCount(1, $this->accountProjects->findByFilter(
                new AccountProjectFilter(accountId: $accountId, projectId: $this->projectId, limit: 0),
            ));
        }
    }

    public function testAnOwnerCannotTransfer(): void
    {
        $this->givenBioscorePurchase(100.0, 1000.0);

        $this->expectException(UnauthorizedActionException::class);

        $this->transfer(user: UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    private function givenBioscorePurchase(float $tonnes, ?float $amount): CreditPurchase
    {
        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request($this->sourceAccountId, $this->projectId, UserId::generate(), $tonnes);
        $creditPurchase->validate(
            purchasedTonnes: $tonnes,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
            reference: 'SERIAL-1',
            certificate: null,
            validatedBy: UserId::generate(),
            amount: $amount,
        );

        $this->purchases->add($creditPurchase);

        return $creditPurchase;
    }

    private function givenPaidContribution(AccountId $accountId, float $tonnes, bool $allocate): ProjectContribution
    {
        $contribution = ProjectContributionMother::paid($accountId, $this->projectId, $tonnes);

        $this->contributions->add($contribution);

        if ($allocate) {
            (new AllocateContributionCreditsService(
                $this->contributions,
                $this->purchases,
                $this->projects,
                new FakeCreditReservationLock(),
            ))(new AllocateContributionCreditsRequest($contribution->id()));
        }

        return $contribution;
    }

    /** @param list<AccountId>|null $destinationAccountIds */
    private function transfer(
        ?CreditPurchaseId $creditPurchaseId = null,
        ?array $destinationAccountIds = null,
        ?float $tonnes = null,
        ?User $user = null,
    ): TransferCreditPurchaseResponse {
        $source = $creditPurchaseId ?? $this->purchases->findByFilter(
            new CreditPurchaseFilter(accountId: $this->sourceAccountId, limit: 1),
        )[0]->id();

        return $this->createSUT()(
            new TransferCreditPurchaseRequest(
                creditPurchaseId: $source,
                destinationAccountIds: $destinationAccountIds ?? [$this->destinationAccountId],
                tonnes: $tonnes,
            ),
            $user ?? UserMother::adminUser(),
        );
    }

    private function lot(TransferCreditPurchaseResponse $response, int $index): CreditPurchase
    {
        $lot = $this->purchases->find($response->lots[$index]->destinationCreditPurchaseId);

        $this->assertInstanceOf(CreditPurchase::class, $lot);

        return $lot;
    }

    /**
     * @param class-string $eventClass
     *
     * @return list<DomainEvent>
     */
    private function eventsOfType(string $eventClass): array
    {
        return array_values(array_filter(
            $this->spySubscriber->events(),
            static fn(DomainEvent $event): bool => $event instanceof $eventClass,
        ));
    }

    private function creditAvailability(): CreditAvailability
    {
        return new CreditAvailability($this->purchases, $this->contributions);
    }

    private function transferableCredit(): TransferableCredit
    {
        return new TransferableCredit($this->contributions, $this->creditAvailability());
    }

    private function createSUT(): TransferCreditPurchaseService
    {
        return new TransferCreditPurchaseService(
            $this->purchases,
            $this->transferableCredit(),
            new SelectProjectService($this->accountProjects, $this->projects, new AccountProjectAuthorizer()),
            new FakeCreditReservationLock(),
            new ReallocateUnderallocatedContributionsService(
                $this->contributions,
                $this->purchases,
                $this->projects,
                new FakeCreditReservationLock(),
            ),
            new CreditPurchaseAuthorizer(),
        );
    }
}
