<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Event\CreditPurchase;

use App\Balance\Domain\Event\CreditPurchase\CancelPurchasesOnAccountRemovedSubscriber;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRegistered;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use PHPUnit\Framework\TestCase;

class CancelPurchasesOnAccountRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToAccountRemoved(): void
    {
        $sut = $this->createSUT(CreditPurchaseRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new AccountRemoved(AccountId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(CreditPurchaseRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new AccountRegistered(
            accountId: AccountId::generate(),
            name: 'account-name',
            active: true,
        )));
    }

    public function testHandleCancelsPendingPurchasesLinkedToAccount(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $pending = CreditPurchaseMother::pending($accountId);
        $repository->add($pending);

        $validated = CreditPurchaseMother::validated($accountId);
        $repository->add($validated);

        $other = CreditPurchaseMother::random();
        $repository->add($other);

        $sut = $this->createSUT($repository);
        $sut->handle(new AccountRemoved($accountId));

        $this->assertSame(CreditPurchaseStatus::Cancelled, $repository->find($pending->id())?->status());
        $this->assertSame(CreditPurchaseStatus::Validated, $repository->find($validated->id())?->status());
        $this->assertCount(0, $repository->findByFilter(new CreditPurchaseFilter(accountId: $accountId, status: CreditPurchaseStatus::Pending, limit: 0)));
    }

    private function createSUT(CreditPurchaseRepository $creditPurchaseRepository): CancelPurchasesOnAccountRemovedSubscriber
    {
        return new CancelPurchasesOnAccountRemovedSubscriber($creditPurchaseRepository);
    }
}
