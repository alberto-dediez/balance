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

        $destination = $this->purchases->find($response->destinationCreditPurchaseId);

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

        $destination = $this->purchases->find($response->destinationCreditPurchaseId);

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

        $second = $this->purchases->find($first->destinationCreditPurchaseId);

        $this->assertInstanceOf(CreditPurchase::class, $second);

        $this->givenPaidContribution($this->destinationAccountId, 20.0, allocate: true);

        $thirdAccountId = AccountId::generate();

        $response = $this->transfer(
            creditPurchaseId: $second->id(),
            destinationAccountId: $thirdAccountId,
            tonnes: 40.0,
        );

        $third = $this->purchases->find($response->destinationCreditPurchaseId);

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

        $destination = $this->purchases->find($response->destinationCreditPurchaseId);

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

        $this->transfer(destinationAccountId: $this->sourceAccountId);
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

    private function transfer(
        ?CreditPurchaseId $creditPurchaseId = null,
        ?AccountId $destinationAccountId = null,
        ?float $tonnes = null,
        ?User $user = null,
    ): TransferCreditPurchaseResponse {
        $source = $creditPurchaseId ?? $this->purchases->findByFilter(
            new CreditPurchaseFilter(accountId: $this->sourceAccountId, limit: 1),
        )[0]->id();

        return $this->createSUT()(
            new TransferCreditPurchaseRequest(
                creditPurchaseId: $source,
                destinationAccountId: $destinationAccountId ?? $this->destinationAccountId,
                tonnes: $tonnes,
            ),
            $user ?? UserMother::adminUser(),
        );
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
