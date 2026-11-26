<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseOrigin;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CreditPurchaseRepositoryTest extends KernelTestCase
{
    public function testAddCreditPurchaseToRepository(): void
    {
        $repository = $this->createSUT();

        $creditPurchase = CreditPurchaseMother::random();

        $this->assertNull($repository->find($creditPurchase->id()));

        $repository->add($creditPurchase);

        $recovered = $repository->find($creditPurchase->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($creditPurchase->id()->equals($recovered->id()));
    }

    public function testFindByFilterByAccountId(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(CreditPurchaseMother::pending($accountId, ProjectId::generate()));
        $repository->add(CreditPurchaseMother::pending($accountId, ProjectId::generate()));
        $repository->add(CreditPurchaseMother::random());

        $result = $repository->findByFilter(new CreditPurchaseFilter(accountId: $accountId, limit: 0));

        $this->assertCount(2, $result);
    }

    public function testFindByFilterByProjectId(): void
    {
        $repository = $this->createSUT();

        $projectId = ProjectId::generate();

        $repository->add(CreditPurchaseMother::pending(AccountId::generate(), $projectId));
        $repository->add(CreditPurchaseMother::random());

        $result = $repository->findByFilter(new CreditPurchaseFilter(projectId: $projectId, limit: 0));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByStatus(): void
    {
        $repository = $this->createSUT();

        $repository->add(CreditPurchaseMother::validated());
        $repository->add(CreditPurchaseMother::random());

        $result = $repository->findByFilter(new CreditPurchaseFilter(status: CreditPurchaseStatus::Validated, limit: 0));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByOrigin(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $assigned = CreditPurchaseMother::assigned($accountId, ProjectId::generate());

        $repository->add($assigned);
        $repository->add(CreditPurchaseMother::validated($accountId, ProjectId::generate()));
        $repository->add(CreditPurchaseMother::receivedLot(reference: 'SERIAL-ORIGIN', accountId: $accountId));

        $result = array_values($repository->findByFilter(
            new CreditPurchaseFilter(accountId: $accountId, origin: CreditPurchaseOrigin::Assigned, limit: 0),
        ));

        $this->assertCount(1, $result);
        $this->assertTrue($assigned->id()->equals($result[0]->id()));

        $this->assertCount(1, $repository->findByFilter(
            new CreditPurchaseFilter(accountId: $accountId, origin: CreditPurchaseOrigin::Received, limit: 0),
        ));
        $this->assertCount(1, $repository->findByFilter(
            new CreditPurchaseFilter(accountId: $accountId, origin: CreditPurchaseOrigin::Requested, limit: 0),
        ));
    }

    public function testFindByFilterOrdersByRequestedAtByDefault(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $middle = CreditPurchaseMother::pendingRequestedAt(new \DateTimeImmutable('-2 days'), $accountId);
        $oldest = CreditPurchaseMother::pendingRequestedAt(new \DateTimeImmutable('-3 days'), $accountId);
        $newest = CreditPurchaseMother::pendingRequestedAt(new \DateTimeImmutable('-1 day'), $accountId);

        $repository->add($middle);
        $repository->add($oldest);
        $repository->add($newest);

        $ascending = array_map(
            static fn($creditPurchase): string => (string) $creditPurchase->id(),
            array_values($repository->findByFilter(new CreditPurchaseFilter(accountId: $accountId, limit: 0))),
        );

        $this->assertSame([(string) $oldest->id(), (string) $middle->id(), (string) $newest->id()], $ascending);

        $descendingFilter = new CreditPurchaseFilter(accountId: $accountId, limit: 0);
        $descendingFilter->orderWay = 'DESC';

        $descending = array_map(
            static fn($creditPurchase): string => (string) $creditPurchase->id(),
            array_values($repository->findByFilter($descendingFilter)),
        );

        $this->assertSame([(string) $newest->id(), (string) $middle->id(), (string) $oldest->id()], $descending);
    }

    public function testExistsByReference(): void
    {
        $repository = $this->createSUT();

        $creditPurchase = CreditPurchaseMother::validated();
        $repository->add($creditPurchase);

        $reference = (string) $creditPurchase->reference();

        $this->assertTrue($repository->existsByReference($reference));
        $this->assertFalse($repository->existsByReference($reference, $creditPurchase->id()));
        $this->assertFalse($repository->existsByReference('does-not-exist'));
    }

    public function testNextLotNumberForReference(): void
    {
        $repository = $this->createSUT();

        $this->assertSame(1, $repository->nextLotNumberFor('does-not-exist'));

        $creditPurchase = CreditPurchaseMother::validated();
        $repository->add($creditPurchase);

        $reference = (string) $creditPurchase->reference();

        $this->assertSame(2, $repository->nextLotNumberFor($reference));

        $repository->add(CreditPurchaseMother::receivedLot(
            reference: $reference,
            purchasedTonnes: 10.0,
            offsetTonnes: 4.0,
            lotNumber: 2,
        ));

        $this->assertSame(3, $repository->nextLotNumberFor($reference));
    }

    public function testTransferredLotsDoNotDuplicateTonnes(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $destinationAccountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $source = CreditPurchaseMother::transferred($accountId, $projectId, 100.0, 70.0);
        $repository->add($source);

        $repository->add(CreditPurchaseMother::receivedLot(
            reference: (string) $source->reference(),
            purchasedTonnes: 100.0,
            offsetTonnes: 30.0,
            lotNumber: 2,
            accountId: $destinationAccountId,
            projectId: $projectId,
        ));

        $this->assertSame(30.0, $repository->balanceFor($accountId, $projectId)->purchasedTonnes());
        $this->assertSame(70.0, $repository->balanceFor($destinationAccountId, $projectId)->purchasedTonnes());
        $this->assertSame(100.0, $repository->purchasedTonnesForProject($projectId));
    }

    public function testBalancesDiscountTheUpperOffsetOfReceivedLots(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $firstDestinationAccountId = AccountId::generate();
        $secondDestinationAccountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $source = CreditPurchaseMother::transferred($accountId, $projectId, 100.0, 60.0);
        $repository->add($source);

        $repository->add(CreditPurchaseMother::receivedLot(
            reference: (string) $source->reference(),
            purchasedTonnes: 100.0,
            offsetTonnes: 60.0,
            lotNumber: 2,
            accountId: $firstDestinationAccountId,
            projectId: $projectId,
        ));

        $repository->add(CreditPurchaseMother::receivedLot(
            reference: (string) $source->reference(),
            purchasedTonnes: 100.0,
            offsetTonnes: 40.0,
            upperOffsetTonnes: 40.0,
            lotNumber: 3,
            accountId: $secondDestinationAccountId,
            projectId: $projectId,
        ));

        $this->assertSame(40.0, $repository->balanceFor($accountId, $projectId)->purchasedTonnes());
        $this->assertSame(40.0, $repository->balanceFor($firstDestinationAccountId, $projectId)->purchasedTonnes());
        $this->assertSame(20.0, $repository->balanceFor($secondDestinationAccountId, $projectId)->purchasedTonnes());
        $this->assertSame(100.0, $repository->purchasedTonnesForProject($projectId));
    }

    public function testBalancesByAccountSumsValidatedPurchasesByProject(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $repository->add(CreditPurchaseMother::validated($accountId, $projectId, 5.0));
        $repository->add(CreditPurchaseMother::validated($accountId, $projectId, 3.0));
        $repository->add(CreditPurchaseMother::pending($accountId, $projectId, 100.0));
        $repository->add(CreditPurchaseMother::validated(AccountId::generate(), $projectId, 1.0));

        $balances = $repository->balancesByAccount($accountId);

        $this->assertCount(1, $balances);
        $this->assertSame(8.0, $balances[0]->purchasedTonnes());

        $balance = $repository->balanceFor($accountId, $projectId);

        $this->assertSame(8.0, $balance->availableTonnes());
    }

    public function testBalanceForUnknownProjectReturnsZero(): void
    {
        $repository = $this->createSUT();

        $balance = $repository->balanceFor(AccountId::generate(), ProjectId::generate());

        $this->assertSame(0.0, $balance->availableTonnes());
        $this->assertSame(0, $balance->purchasesCount());
    }

    public function testLatestValidatedForReturnsTheMostRecentlyResolvedPurchase(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $repository->add(CreditPurchaseMother::validatedAt(new \DateTimeImmutable('-3 days'), $accountId, $projectId, 5.0));
        $latest = CreditPurchaseMother::validatedAt(new \DateTimeImmutable('-1 day'), $accountId, $projectId, 7.0);
        $repository->add($latest);
        $repository->add(CreditPurchaseMother::pending($accountId, $projectId, 100.0));
        $repository->add(CreditPurchaseMother::validatedAt(new \DateTimeImmutable('NOW'), $accountId, ProjectId::generate(), 9.0));

        $found = $repository->latestValidatedFor($accountId, $projectId);

        $this->assertNotNull($found);
        $this->assertTrue($latest->id()->equals($found->id()));
    }

    public function testLatestValidatedForWithoutValidatedPurchasesIsNull(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $repository->add(CreditPurchaseMother::pending($accountId, $projectId));

        $this->assertNull($repository->latestValidatedFor($accountId, $projectId));
    }

    public function testLatestWithBillingForReturnsTheMostRecentRequestWithBilling(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(CreditPurchaseMother::pendingWithBilling(
            $accountId,
            billing: CreditPurchaseBillingMother::from(legalName: 'Antigua SL'),
            requestedAt: new \DateTimeImmutable('-3 days'),
        ));
        $latest = CreditPurchaseMother::pendingWithBilling(
            $accountId,
            billing: CreditPurchaseBillingMother::from(legalName: 'Reciente SL'),
            requestedAt: new \DateTimeImmutable('-1 day'),
        );
        $repository->add($latest);
        $repository->add(CreditPurchaseMother::pendingRequestedAt(new \DateTimeImmutable('NOW'), $accountId));
        $repository->add(CreditPurchaseMother::pendingWithBilling(AccountId::generate(), requestedAt: new \DateTimeImmutable('NOW')));

        $found = $repository->latestWithBillingFor($accountId);

        $this->assertNotNull($found);
        $this->assertTrue($latest->id()->equals($found->id()));
        $this->assertSame('Reciente SL', $found->billing()?->legalName());
    }

    public function testLatestWithBillingForWithoutRequestsWithBillingIsNull(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(CreditPurchaseMother::pending($accountId));
        $repository->add(CreditPurchaseMother::pendingWithBilling(AccountId::generate()));

        $this->assertNull($repository->latestWithBillingFor($accountId));
    }

    public function testLatestValidatedPurchasesReturnsOnePurchasePerAccountAndProject(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $repository->add(CreditPurchaseMother::validatedAt(new \DateTimeImmutable('-3 days'), $accountId, $projectId, 5.0));
        $latest = CreditPurchaseMother::validatedAt(new \DateTimeImmutable('-1 day'), $accountId, $projectId, 7.0);
        $repository->add($latest);
        $other = CreditPurchaseMother::validatedAt(new \DateTimeImmutable('-2 days'), AccountId::generate(), ProjectId::generate(), 3.0);
        $repository->add($other);
        $repository->add(CreditPurchaseMother::pending($accountId, $projectId));

        $purchases = $repository->latestValidatedPurchases();

        $ids = array_map(static fn($creditPurchase): string => (string) $creditPurchase->id(), $purchases);

        $this->assertCount(2, $purchases);
        $this->assertContains((string) $latest->id(), $ids);
        $this->assertContains((string) $other->id(), $ids);
    }

    public function testNotifiedThresholdsArePersisted(): void
    {
        $repository = $this->createSUT();

        $creditPurchase = CreditPurchaseMother::validatedAt(
            new \DateTimeImmutable('-1 day'),
            AccountId::generate(),
            ProjectId::generate(),
            10.0,
            notifiedThresholds: [CreditAlertThreshold::Warning, CreditAlertThreshold::High],
        );

        $repository->add($creditPurchase);

        $recovered = $repository->find($creditPurchase->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($recovered->hasNotified(CreditAlertThreshold::High));
        $this->assertFalse($recovered->hasNotified(CreditAlertThreshold::Depleted));
    }

    public function testFindByFilterWithAvailableTonnes(): void
    {
        $repository = $this->createSUT();
        $this->addAvailableTonnesDataSet($repository);

        $filter = new CreditPurchaseFilter(limit: 0, withAvailableTonnes: true);

        $this->assertCount(3, $repository->findByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterWithoutAvailableTonnes(): void
    {
        $repository = $this->createSUT();
        $this->addAvailableTonnesDataSet($repository);

        $filter = new CreditPurchaseFilter(limit: 0, withAvailableTonnes: false);

        $this->assertCount(4, $repository->findByFilter($filter));
        $this->assertSame(4, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterIgnoresAvailableTonnesWhenNull(): void
    {
        $repository = $this->createSUT();
        $this->addAvailableTonnesDataSet($repository);

        $this->assertCount(7, $repository->findByFilter(new CreditPurchaseFilter(limit: 0)));
    }

    public function testAvailableTonnesFilterKeepsPaginationConsistent(): void
    {
        $repository = $this->createSUT();
        $this->addAvailableTonnesDataSet($repository);

        $firstPage = new CreditPurchaseFilter(limit: 2, withAvailableTonnes: true);
        $secondPage = new CreditPurchaseFilter(limit: 2, withAvailableTonnes: true);
        $secondPage->page = 1;

        $ids = array_map(
            static fn(CreditPurchase $creditPurchase): string => (string) $creditPurchase->id(),
            [...array_values($repository->findByFilter($firstPage)), ...array_values($repository->findByFilter($secondPage))],
        );

        $this->assertCount(3, $ids);
        $this->assertCount(3, array_unique($ids));
        $this->assertSame(3, $repository->totalResultsByFilter($firstPage));
    }

    public function testAvailableTonnesFilterCombinesWithStatus(): void
    {
        $repository = $this->createSUT();
        $this->addAvailableTonnesDataSet($repository);

        $this->assertCount(0, $repository->findByFilter(
            new CreditPurchaseFilter(status: CreditPurchaseStatus::Rejected, limit: 0, withAvailableTonnes: true),
        ));
        $this->assertCount(1, $repository->findByFilter(
            new CreditPurchaseFilter(status: CreditPurchaseStatus::Rejected, limit: 0, withAvailableTonnes: false),
        ));
    }

    public function testAvailableTonnesFilterHonoursOffsetsTransfersAndTolerance(): void
    {
        $repository = $this->createSUT();
        $projectContributionRepository = $this->createProjectContributionRepository();

        $transferredAndAllocated = CreditPurchaseMother::transferred(tonnes: 100.0, transferredTonnes: 60.0);
        $receivedLowerLot = CreditPurchaseMother::receivedLot(reference: 'REF-LOWER', purchasedTonnes: 100.0, offsetTonnes: 60.0);
        $receivedMiddleLot = CreditPurchaseMother::receivedLot(reference: 'REF-MIDDLE', purchasedTonnes: 100.0, offsetTonnes: 40.0, upperOffsetTonnes: 40.0);
        $spentWithinTolerance = CreditPurchaseMother::validated(tonnes: 10.0);
        $receivedMiddleLotWithTonnes = CreditPurchaseMother::receivedLot(reference: 'REF-MIDDLE-AVAILABLE', purchasedTonnes: 100.0, offsetTonnes: 40.0, upperOffsetTonnes: 40.0);
        $availableAboveTolerance = CreditPurchaseMother::validated(tonnes: 10.0);

        $allocations = [
            [$transferredAndAllocated, 40.0, 0.0],
            [$receivedLowerLot, 40.0, 60.0],
            [$receivedMiddleLot, 20.0, 40.0],
            [$spentWithinTolerance, 9.9999995, 0.0],
            [$receivedMiddleLotWithTonnes, 10.0, 40.0],
            [$availableAboveTolerance, 9.99999, 0.0],
        ];

        foreach ($allocations as [$creditPurchase, $tonnes, $startTonnes]) {
            $repository->add($creditPurchase);

            $contribution = ProjectContributionMother::ownCreditFunded(
                $creditPurchase->accountId(),
                $creditPurchase->projectId(),
                $tonnes,
            );
            $contribution->allocateCredits([
                new AllocatedCreditLine(
                    position: 0,
                    creditPurchaseId: $creditPurchase->id(),
                    purchaseReference: $creditPurchase->reference(),
                    tonnes: $tonnes,
                    startTonnes: $startTonnes,
                ),
            ]);
            $projectContributionRepository->add($contribution);
        }

        $this->assertSame(
            $this->sortedIds([$receivedMiddleLotWithTonnes, $availableAboveTolerance]),
            $this->sortedIds($repository->findByFilter(new CreditPurchaseFilter(limit: 0, withAvailableTonnes: true))),
        );
        $this->assertSame(
            $this->sortedIds([$transferredAndAllocated, $receivedLowerLot, $receivedMiddleLot, $spentWithinTolerance]),
            $this->sortedIds($repository->findByFilter(new CreditPurchaseFilter(limit: 0, withAvailableTonnes: false))),
        );
    }

    abstract protected function createSUT(): CreditPurchaseRepository;

    abstract protected function createProjectContributionRepository(): ProjectContributionRepository;

    /**
     * @param array<CreditPurchase> $creditPurchases
     *
     * @return list<string>
     */
    private function sortedIds(array $creditPurchases): array
    {
        $ids = array_map(
            static fn(CreditPurchase $creditPurchase): string => (string) $creditPurchase->id(),
            array_values($creditPurchases),
        );
        sort($ids);

        return $ids;
    }

    private function addAvailableTonnesDataSet(CreditPurchaseRepository $repository): void
    {
        $projectContributionRepository = $this->createProjectContributionRepository();

        $spent = CreditPurchaseMother::validated(tonnes: 10.0);
        $partial = CreditPurchaseMother::validated(tonnes: 10.0);
        $cancelled = CreditPurchaseMother::pending();
        $cancelled->cancel();

        $repository->add($spent);
        $repository->add(CreditPurchaseMother::transferred(tonnes: 100.0, transferredTonnes: 100.0));
        $repository->add($partial);
        $repository->add(CreditPurchaseMother::validated(tonnes: 10.0));
        $repository->add(CreditPurchaseMother::pending());
        $repository->add(CreditPurchaseMother::rejected());
        $repository->add($cancelled);

        foreach ([[$spent, 10.0], [$partial, 2.0]] as [$creditPurchase, $tonnes]) {
            $contribution = ProjectContributionMother::ownCreditFunded(
                $creditPurchase->accountId(),
                $creditPurchase->projectId(),
                $tonnes,
            );
            $contribution->allocateCredits([
                new AllocatedCreditLine(
                    position: 0,
                    creditPurchaseId: $creditPurchase->id(),
                    purchaseReference: $creditPurchase->reference(),
                    tonnes: $tonnes,
                    startTonnes: 0.0,
                ),
            ]);
            $projectContributionRepository->add($contribution);
        }
    }
}
