<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
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

    public function testFindOneByReference(): void
    {
        $repository = $this->createSUT();

        $creditPurchase = CreditPurchaseMother::validated();
        $repository->add($creditPurchase);

        $found = $repository->findOneByReference((string) $creditPurchase->reference());

        $this->assertNotNull($found);
        $this->assertTrue($creditPurchase->id()->equals($found->id()));

        $this->assertNull($repository->findOneByReference('does-not-exist'));
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

    abstract protected function createSUT(): CreditPurchaseRepository;
}
