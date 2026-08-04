<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Cancel;

use App\Balance\Application\CreditPurchase\Cancel\CancelCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Cancel\CancelCreditPurchaseService;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseCancelled;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseNotPendingException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CancelCreditPurchaseServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testCancelsOwnPendingPurchase(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $creditPurchase = CreditPurchaseMother::pending($accountId);
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut(new CancelCreditPurchaseRequest($creditPurchase->id()), $user);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseCancelled::class, $lastEvent);
    }

    public function testThrowsWhenNotPending(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $creditPurchase = CreditPurchaseMother::validated($accountId);
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->expectException(CreditPurchaseNotPendingException::class);

        $sut(new CancelCreditPurchaseRequest($creditPurchase->id()), $user);
    }

    public function testThrowsWhenCancellingAnotherAccountsPurchase(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $creditPurchase = CreditPurchaseMother::pending(AccountId::generate());
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new CancelCreditPurchaseRequest($creditPurchase->id()), $user);
    }

    private function createSUT(CreditPurchaseRepository $repository): CancelCreditPurchaseService
    {
        return new CancelCreditPurchaseService($repository, new CreditPurchaseAuthorizer());
    }
}
