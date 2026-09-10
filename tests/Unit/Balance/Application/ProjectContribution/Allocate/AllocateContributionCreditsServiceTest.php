<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Allocate;

use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsRequest;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditsUnderallocated;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Application\ProjectContribution\FakeCreditReservationLock;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AllocateContributionCreditsServiceTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testTheOldestLotIsConsumedFirst(): void
    {
        $this->givenCompensationProject();

        $oldest = $this->givenValidatedPurchase(3.0, '-3 days');
        $newest = $this->givenValidatedPurchase(10.0, '-1 day');

        $contribution = $this->givenPaidContribution(5.0);

        $this->allocate($contribution);

        $allocations = $contribution->creditAllocations();

        $this->assertCount(2, $allocations);
        $this->assertTrue($oldest->id()->equals($allocations[0]->creditPurchaseId()));
        $this->assertSame(3.0, $allocations[0]->tonnes());
        $this->assertTrue($newest->id()->equals($allocations[1]->creditPurchaseId()));
        $this->assertSame(2.0, $allocations[1]->tonnes());
        $this->assertSame([$oldest->reference(), $newest->reference()], $contribution->masterSerials());
    }

    public function testALotAlreadyPartiallyConsumedOnlyOffersWhatIsLeft(): void
    {
        $this->givenCompensationProject();

        $purchase = $this->givenValidatedPurchase(10.0, '-2 days');

        $first = $this->givenPaidContribution(4.0);
        $this->allocate($first);

        $second = $this->givenPaidContribution(3.0);
        $this->allocate($second);

        $this->assertSame(3.0, $second->allocatedTonnes());
        $this->assertSame(
            7.0,
            $this->contributions->allocatedTonnesByPurchase($this->accountId, $this->projectId)[(string) $purchase->id()],
        );
    }

    public function testEachLotRecordsTheTonneRangeTakenFromItsPurchase(): void
    {
        $this->givenCompensationProject();

        $oldest = $this->givenValidatedPurchase(3.0, '-3 days');
        $newest = $this->givenValidatedPurchase(10.0, '-1 day');

        $contribution = $this->givenPaidContribution(5.0);

        $this->allocate($contribution);

        $this->assertSame(
            [
                ['serial' => $oldest->reference(), 'startTonnes' => 0.0, 'endTonnes' => 3.0],
                ['serial' => $newest->reference(), 'startTonnes' => 0.0, 'endTonnes' => 2.0],
            ],
            $contribution->creditLots(),
        );
    }

    public function testTwoContributionsNeverShareTheSameLotRange(): void
    {
        $this->givenCompensationProject();

        $purchase = $this->givenValidatedPurchase(10.0, '-2 days');

        $first = $this->givenPaidContribution(4.0);
        $this->allocate($first);

        $second = $this->givenPaidContribution(3.0);
        $this->allocate($second);

        $this->assertSame(
            [['serial' => $purchase->reference(), 'startTonnes' => 0.0, 'endTonnes' => 4.0]],
            $first->creditLots(),
        );
        $this->assertSame(
            [['serial' => $purchase->reference(), 'startTonnes' => 4.0, 'endTonnes' => 7.0]],
            $second->creditLots(),
        );
    }

    public function testASocialProjectRetiresNoCredit(): void
    {
        $project = ProjectMother::contributable(projectType: ProjectType::SocialProject, id: $this->projectId);
        $this->projects->add($project);

        $this->givenValidatedPurchase(10.0, '-1 day');

        $contribution = $this->givenPaidContribution(2.0);

        $this->allocate($contribution);

        $this->assertFalse($contribution->hasCreditAllocations());
    }

    public function testAllocatingTwiceLeavesTheFirstAssignmentAlone(): void
    {
        $this->givenCompensationProject();
        $this->givenValidatedPurchase(10.0, '-1 day');

        $contribution = $this->givenPaidContribution(2.0);

        $this->allocate($contribution);
        $this->allocate($contribution);

        $this->assertCount(1, $contribution->creditAllocations());
        $this->assertSame(2.0, $contribution->allocatedTonnes());
    }

    public function testCreditShortFallIsReported(): void
    {
        $this->givenCompensationProject();
        $this->givenValidatedPurchase(1.0, '-1 day');

        $contribution = $this->givenPaidContribution(4.0);

        $spy = new SpyDomainEventSubscriber();

        $this->allocate($contribution);

        $this->assertSame(1.0, $contribution->allocatedTonnes());

        $underallocated = array_filter(
            $spy->events(),
            static fn(object $event): bool => $event instanceof ProjectContributionCreditsUnderallocated,
        );

        $this->assertCount(1, $underallocated);
    }

    public function testAnOwnCreditContributionOnlyConsumesTheLotsTheClientPaid(): void
    {
        $this->givenCompensationProject();

        $this->givenValidatedPurchase(10.0, '-3 days', CreditPurchasePaymentType::PaidByBioscore);
        $own = $this->givenValidatedPurchase(10.0, '-1 day');

        $contribution = ProjectContributionMother::ownCreditFunded($this->accountId, $this->projectId, tonnes: 4.0);
        $this->contributions->add($contribution);

        $this->allocate($contribution);

        $allocations = $contribution->creditAllocations();

        $this->assertCount(1, $allocations);
        $this->assertTrue($own->id()->equals($allocations[0]->creditPurchaseId()));
        $this->assertSame(4.0, $allocations[0]->tonnes());
    }

    public function testAReceivedLotIsPublishedFromItsOffset(): void
    {
        $this->givenCompensationProject();

        $lot = CreditPurchaseMother::receivedLot(
            reference: 'SERIAL-1',
            purchasedTonnes: 100.0,
            offsetTonnes: 30.0,
            lotNumber: 2,
            accountId: $this->accountId,
            projectId: $this->projectId,
        );
        $this->purchases->add($lot);

        $contribution = $this->givenPaidContribution(20.0);

        $this->allocate($contribution);

        $this->assertSame(
            [['serial' => 'SERIAL-1', 'startTonnes' => 30.0, 'endTonnes' => 50.0]],
            $contribution->creditLots(),
        );
    }

    private function givenCompensationProject(): void
    {
        $this->projects->add(ProjectMother::contributable(
            projectType: ProjectType::EmissionCompensation,
            id: $this->projectId,
        ));
    }

    private function givenValidatedPurchase(
        float $tonnes,
        string $resolvedAt,
        CreditPurchasePaymentType $paymentType = CreditPurchasePaymentType::PaidByClient,
    ): CreditPurchase {
        $purchase = CreditPurchaseMother::validatedAt(
            new \DateTimeImmutable($resolvedAt),
            $this->accountId,
            $this->projectId,
            $tonnes,
            $paymentType,
        );

        $this->purchases->add($purchase);

        return $purchase;
    }

    private function givenPaidContribution(float $tonnes): ProjectContribution
    {
        $contribution = ProjectContributionMother::paid($this->accountId, $this->projectId, $tonnes);

        $this->contributions->add($contribution);

        return $contribution;
    }

    private function allocate(ProjectContribution $contribution): void
    {
        (new AllocateContributionCreditsService(
            $this->contributions,
            $this->purchases,
            $this->projects,
            new FakeCreditReservationLock(),
        ))(new AllocateContributionCreditsRequest($contribution->id()));
    }
}
