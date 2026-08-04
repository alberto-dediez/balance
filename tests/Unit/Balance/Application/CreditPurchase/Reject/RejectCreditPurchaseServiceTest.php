<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Reject;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditPurchase\Reject\RejectCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Reject\RejectCreditPurchaseService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseNotPendingException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRejected;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RejectCreditPurchaseServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRejectsPendingPurchase(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::pending();
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $sut(new RejectCreditPurchaseRequest($creditPurchase->id(), 'Sin disponibilidad'), $user);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseRejected::class, $lastEvent);
    }

    public function testThrowsWhenNotPending(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::validated();
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $this->expectException(CreditPurchaseNotPendingException::class);

        $sut(new RejectCreditPurchaseRequest($creditPurchase->id(), 'motivo'), $user);
    }

    public function testThrowsWhenUserIsNotAdmin(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::pending();
        $repository->add($creditPurchase);

        $sut = $this->createSUT($repository);
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RejectCreditPurchaseRequest($creditPurchase->id(), 'motivo'), $user);
    }

    private function createSUT(CreditPurchaseRepository $repository): RejectCreditPurchaseService
    {
        return new RejectCreditPurchaseService($repository, new CreditPurchaseAuthorizer());
    }
}
