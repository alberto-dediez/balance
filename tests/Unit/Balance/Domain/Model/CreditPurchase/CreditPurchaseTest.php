<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseCancelled;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseNotPendingException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRejected;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRequested;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseValidated;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CreditPurchaseTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRequestPublishesCreditPurchaseRequested(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();
        $requestedBy = UserId::generate();

        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());
        $creditPurchase->request($accountId, $projectId, $requestedBy, 10.0);

        $this->assertNotNull($creditPurchase->accountId());
        $this->assertNotNull($creditPurchase->projectId());
        $this->assertTrue($accountId->equals($creditPurchase->accountId()));
        $this->assertTrue($projectId->equals($creditPurchase->projectId()));
        $this->assertSame(10.0, $creditPurchase->requestedTonnes());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseRequested::class, $lastEvent);
    }

    public function testRequestWithZeroTonnesThrows(): void
    {
        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate());

        $this->expectException(\InvalidArgumentException::class);

        $creditPurchase->request(AccountId::generate(), ProjectId::generate(), UserId::generate(), 0.0);
    }

    public function testValidatePublishesCreditPurchaseValidated(): void
    {
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);

        $creditPurchase->validate(10.0, 'REF-1', null, UserId::generate());

        $this->assertSame(10.0, $creditPurchase->purchasedTonnes());
        $this->assertFalse($creditPurchase->isPartiallyFulfilled());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseValidated::class, $lastEvent);
        $this->assertFalse($lastEvent->isPartiallyFulfilled());
    }

    public function testValidateWithLessTonnesThanRequestedIsPartiallyFulfilled(): void
    {
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);

        $creditPurchase->validate(6.0, 'REF-2', null, UserId::generate());

        $this->assertTrue($creditPurchase->isPartiallyFulfilled());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseValidated::class, $lastEvent);
        $this->assertTrue($lastEvent->isPartiallyFulfilled());
    }

    public function testValidateWithMoreTonnesThanRequestedIsNotPartial(): void
    {
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);

        $creditPurchase->validate(12.0, 'REF-3', null, UserId::generate());

        $this->assertFalse($creditPurchase->isPartiallyFulfilled());
    }

    public function testValidateNotPendingThrows(): void
    {
        $creditPurchase = CreditPurchaseMother::validated();

        $this->expectException(CreditPurchaseNotPendingException::class);

        $creditPurchase->validate(1.0, 'REF-4', null, UserId::generate());
    }

    public function testRejectPublishesCreditPurchaseRejected(): void
    {
        $creditPurchase = CreditPurchaseMother::pending();

        $creditPurchase->reject('Sin disponibilidad', UserId::generate());

        $this->assertSame('Sin disponibilidad', $creditPurchase->rejectionReason());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseRejected::class, $lastEvent);
    }

    public function testRejectNotPendingThrows(): void
    {
        $creditPurchase = CreditPurchaseMother::rejected();

        $this->expectException(CreditPurchaseNotPendingException::class);

        $creditPurchase->reject('otro motivo', UserId::generate());
    }

    public function testCancelPublishesCreditPurchaseCancelled(): void
    {
        $creditPurchase = CreditPurchaseMother::pending();

        $creditPurchase->cancel();

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseCancelled::class, $lastEvent);
    }

    public function testCancelNotPendingThrows(): void
    {
        $creditPurchase = CreditPurchaseMother::validated();

        $this->expectException(CreditPurchaseNotPendingException::class);

        $creditPurchase->cancel();
    }

    public function testAvailableTonnesIsZeroUnlessValidated(): void
    {
        $creditPurchase = CreditPurchaseMother::pending();

        $this->assertSame(0.0, $creditPurchase->availableTonnes());
    }

    public function testRebuildReconstructsState(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();
        $id = CreditPurchaseId::generate();

        $requested = new CreditPurchaseRequested(
            creditPurchaseId: $id,
            accountId: $accountId,
            projectId: $projectId,
            requestedBy: UserId::generate(),
            requestedTonnes: 5.0,
        );

        $creditPurchase = new CreditPurchase(id: $id, events: [$requested]);
        $creditPurchase->rebuild();

        $this->assertNotNull($creditPurchase->accountId());
        $this->assertNotNull($creditPurchase->projectId());
        $this->assertTrue($accountId->equals($creditPurchase->accountId()));
        $this->assertTrue($projectId->equals($creditPurchase->projectId()));
        $this->assertSame(5.0, $creditPurchase->requestedTonnes());
    }
}
