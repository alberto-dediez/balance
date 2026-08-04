<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

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

    abstract protected function createSUT(): CreditPurchaseRepository;
}
