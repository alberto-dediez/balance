<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseCancelled;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseNotPendingException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRangeCorrected;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseReceived;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRejected;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRequested;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseTransferred;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseValidated;
use App\Balance\Domain\Model\CreditPurchase\CreditThresholdReached;
use App\Balance\Domain\Model\CreditPurchase\InsufficientTransferableCreditException;
use App\Balance\Domain\Model\CreditPurchase\NonTransferableCreditPurchaseException;
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

        $creditPurchase->validate(10.0, CreditPurchasePaymentType::PaidByClient, 'REF-1', null, UserId::generate());

        $this->assertSame(10.0, $creditPurchase->purchasedTonnes());
        $this->assertFalse($creditPurchase->isPartiallyFulfilled());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseValidated::class, $lastEvent);
        $this->assertFalse($lastEvent->isPartiallyFulfilled());
    }

    public function testValidateKeepsThePaymentType(): void
    {
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);

        $creditPurchase->validate(10.0, CreditPurchasePaymentType::PaidByBioscore, 'REF-1', null, UserId::generate());

        $this->assertSame(CreditPurchasePaymentType::PaidByBioscore, $creditPurchase->paymentType());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseValidated::class, $lastEvent);
        $this->assertSame(CreditPurchasePaymentType::PaidByBioscore, $lastEvent->paymentType());

        $rebuilt = CreditPurchaseValidated::rebuildFromPayload(
            $lastEvent->id(),
            $lastEvent->occurredOn(),
            $lastEvent->payload(),
        );

        $this->assertSame(CreditPurchasePaymentType::PaidByBioscore, $rebuilt->paymentType());
    }

    public function testValidateWithLessTonnesThanRequestedIsPartiallyFulfilled(): void
    {
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);

        $creditPurchase->validate(6.0, CreditPurchasePaymentType::PaidByClient, 'REF-2', null, UserId::generate());

        $this->assertTrue($creditPurchase->isPartiallyFulfilled());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseValidated::class, $lastEvent);
        $this->assertTrue($lastEvent->isPartiallyFulfilled());
    }

    public function testValidateWithMoreTonnesThanRequestedIsNotPartial(): void
    {
        $creditPurchase = CreditPurchaseMother::pending(tonnes: 10.0);

        $creditPurchase->validate(12.0, CreditPurchasePaymentType::PaidByClient, 'REF-3', null, UserId::generate());

        $this->assertFalse($creditPurchase->isPartiallyFulfilled());
    }

    public function testValidateNotPendingThrows(): void
    {
        $creditPurchase = CreditPurchaseMother::validated();

        $this->expectException(CreditPurchaseNotPendingException::class);

        $creditPurchase->validate(1.0, CreditPurchasePaymentType::PaidByClient, 'REF-4', null, UserId::generate());
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

    public function testTransferVoidsTheRemainderAndProratesTheAmount(): void
    {
        $creditPurchase = $this->bioscorePurchase(100.0, 1000.0);

        $creditPurchase->transfer(
            tonnes: 70.0,
            amount: 700.0,
            destinationCreditPurchaseId: CreditPurchaseId::generate(),
            destinationAccountId: AccountId::generate(),
            transferredBy: UserId::generate(),
        );

        $this->assertSame(70.0, $creditPurchase->voidedTonnes());
        $this->assertSame(30.0, $creditPurchase->availableTonnes());
        $this->assertSame(300.0, $creditPurchase->amount());
        $this->assertSame(10.0, $creditPurchase->pricePerTonne());
        $this->assertTrue($creditPurchase->isTransferred());

        $this->assertInstanceOf(CreditPurchaseTransferred::class, $this->spySubscriber->lastEvent());
    }

    public function testTransferOfAPurchaseWithoutAmountKeepsTheAmountNull(): void
    {
        $creditPurchase = $this->bioscorePurchase(100.0, null);

        $creditPurchase->transfer(
            tonnes: 40.0,
            amount: 0.0,
            destinationCreditPurchaseId: CreditPurchaseId::generate(),
            destinationAccountId: AccountId::generate(),
            transferredBy: UserId::generate(),
        );

        $this->assertNull($creditPurchase->amount());
        $this->assertNull($creditPurchase->pricePerTonne());
        $this->assertSame(60.0, $creditPurchase->availableTonnes());
    }

    public function testTransferOfANonValidatedPurchaseThrows(): void
    {
        $creditPurchase = CreditPurchaseMother::pending();

        $this->expectException(NonTransferableCreditPurchaseException::class);

        $creditPurchase->transfer(
            tonnes: 1.0,
            amount: 0.0,
            destinationCreditPurchaseId: CreditPurchaseId::generate(),
            destinationAccountId: AccountId::generate(),
            transferredBy: UserId::generate(),
        );
    }

    public function testTransferOfAPurchasePaidByTheClientThrows(): void
    {
        $creditPurchase = CreditPurchaseMother::validated(tonnes: 100.0);

        $this->expectException(NonTransferableCreditPurchaseException::class);

        $creditPurchase->transfer(
            tonnes: 1.0,
            amount: 0.0,
            destinationCreditPurchaseId: CreditPurchaseId::generate(),
            destinationAccountId: AccountId::generate(),
            transferredBy: UserId::generate(),
        );
    }

    public function testTransferOfZeroTonnesThrows(): void
    {
        $creditPurchase = $this->bioscorePurchase(100.0, 1000.0);

        $this->expectException(InsufficientTransferableCreditException::class);

        $creditPurchase->transfer(
            tonnes: 0.0,
            amount: 0.0,
            destinationCreditPurchaseId: CreditPurchaseId::generate(),
            destinationAccountId: AccountId::generate(),
            transferredBy: UserId::generate(),
        );
    }

    public function testReceiveStartsAValidatedLotWithTheOffsetOfTheSource(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $destination = new CreditPurchase(id: CreditPurchaseId::generate());
        $destination->receive(
            sourceCreditPurchaseId: CreditPurchaseId::generate(),
            accountId: $accountId,
            projectId: $projectId,
            purchasedTonnes: 100.0,
            offsetTonnes: 30.0,
            upperOffsetTonnes: 0.0,
            amount: 700.0,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
            reference: 'SERIAL-1',
            lotNumber: 2,
            certificate: null,
            transferredBy: UserId::generate(),
        );

        $this->assertNotNull($destination->accountId());
        $this->assertNotNull($destination->projectId());
        $this->assertTrue($accountId->equals($destination->accountId()));
        $this->assertTrue($projectId->equals($destination->projectId()));
        $this->assertSame(70.0, $destination->availableTonnes());
        $this->assertSame(70.0, $destination->requestedTonnes());
        $this->assertSame(30.0, $destination->offsetTonnes());
        $this->assertSame(0.0, $destination->upperOffsetTonnes());
        $this->assertSame(100.0, $destination->ceilingTonnes());
        $this->assertSame(2, $destination->lotNumber());
        $this->assertSame(10.0, $destination->pricePerTonne());
        $this->assertFalse($destination->isPartiallyFulfilled());
        $this->assertFalse($destination->isTransferred());
        $this->assertSame([], $destination->notifiedThresholds());

        $this->assertInstanceOf(CreditPurchaseReceived::class, $this->spySubscriber->lastEvent());
    }

    public function testReceiveWithAnUpperOffsetOwnsOnlyItsRange(): void
    {
        $destination = CreditPurchaseMother::receivedLot(
            reference: 'SERIAL-1',
            purchasedTonnes: 100.0,
            offsetTonnes: 40.0,
            upperOffsetTonnes: 40.0,
            lotNumber: 3,
        );

        $this->assertSame(20.0, $destination->availableTonnes());
        $this->assertSame(20.0, $destination->requestedTonnes());
        $this->assertSame(60.0, $destination->ceilingTonnes());
        $this->assertSame(40.0, $destination->upperOffsetTonnes());
        $this->assertFalse($destination->isTransferred());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseReceived::class, $lastEvent);
        $this->assertSame(40.0, $lastEvent->upperOffsetTonnes());
        $this->assertSame(20.0, $lastEvent->receivedTonnes());
    }

    public function testReceivedPayloadWithoutUpperOffsetIsRebuiltWithZero(): void
    {
        CreditPurchaseMother::receivedLot(reference: 'SERIAL-1', upperOffsetTonnes: 10.0);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseReceived::class, $lastEvent);

        $payload = $lastEvent->payload();
        unset($payload['upperOffsetTonnes']);

        $rebuilt = CreditPurchaseReceived::rebuildFromPayload($lastEvent->id(), $lastEvent->occurredOn(), $payload);

        $this->assertSame(0.0, $rebuilt->upperOffsetTonnes());
        $this->assertSame(70.0, $rebuilt->receivedTonnes());
    }

    public function testCeilingTonnesMovesDownWithEachTransfer(): void
    {
        $creditPurchase = $this->bioscorePurchase(100.0, null);

        $this->assertSame(100.0, $creditPurchase->ceilingTonnes());

        foreach ([40.0, 20.0] as $tonnes) {
            $creditPurchase->transfer(
                tonnes: $tonnes,
                amount: 0.0,
                destinationCreditPurchaseId: CreditPurchaseId::generate(),
                destinationAccountId: AccountId::generate(),
                transferredBy: UserId::generate(),
            );
        }

        $this->assertSame(40.0, $creditPurchase->ceilingTonnes());
        $this->assertSame(40.0, $creditPurchase->availableTonnes());
    }

    public function testCorrectRangePublishesCreditPurchaseRangeCorrected(): void
    {
        $lot = CreditPurchaseMother::receivedLot(reference: 'SERIAL-1', offsetTonnes: 40.0, lotNumber: 3);

        $lot->correctRange(offsetTonnes: 40.0, upperOffsetTonnes: 40.0);

        $this->assertSame(40.0, $lot->offsetTonnes());
        $this->assertSame(40.0, $lot->upperOffsetTonnes());
        $this->assertSame(20.0, $lot->requestedTonnes());
        $this->assertSame(20.0, $lot->availableTonnes());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseRangeCorrected::class, $lastEvent);
        $this->assertSame(40.0, $lastEvent->previousOffsetTonnes());
        $this->assertSame(0.0, $lastEvent->previousUpperOffsetTonnes());
        $this->assertSame(40.0, $lastEvent->offsetTonnes());
        $this->assertSame(40.0, $lastEvent->upperOffsetTonnes());
    }

    public function testCorrectRangeDoesNothingWhenTheRangeMatches(): void
    {
        $lot = CreditPurchaseMother::receivedLot(reference: 'SERIAL-1', offsetTonnes: 40.0, upperOffsetTonnes: 40.0);
        $eventsBefore = count($this->spySubscriber->events());

        $lot->correctRange(offsetTonnes: 40.0000001, upperOffsetTonnes: 40.0);

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testCorrectRangeOfTheFirstLotThrows(): void
    {
        $creditPurchase = $this->bioscorePurchase(100.0, null);

        $this->expectException(NonTransferableCreditPurchaseException::class);

        $creditPurchase->correctRange(offsetTonnes: 10.0, upperOffsetTonnes: 0.0);
    }

    public function testCorrectRangeOfANonValidatedLotThrows(): void
    {
        $creditPurchase = new CreditPurchase(id: CreditPurchaseId::generate(), lotNumber: 2);

        $this->expectException(NonTransferableCreditPurchaseException::class);

        $creditPurchase->correctRange(offsetTonnes: 10.0, upperOffsetTonnes: 0.0);
    }

    private function bioscorePurchase(float $tonnes, ?float $amount): CreditPurchase
    {
        $creditPurchase = CreditPurchaseMother::pending(tonnes: $tonnes);
        $creditPurchase->validate(
            purchasedTonnes: $tonnes,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
            reference: 'SERIAL-' . (string) $creditPurchase->id(),
            certificate: null,
            validatedBy: UserId::generate(),
            amount: $amount,
        );

        return $creditPurchase;
    }

    public function testReachThresholdPublishesCreditThresholdReached(): void
    {
        $creditPurchase = CreditPurchaseMother::validated(tonnes: 10.0);

        $creditPurchase->reachThreshold(CreditAlertThreshold::Warning, 8.5, 1.5, 1.5);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditThresholdReached::class, $lastEvent);
        $this->assertSame(CreditAlertThreshold::Warning, $lastEvent->threshold());
        $this->assertSame(8.5, $lastEvent->consumedTonnes());
        $this->assertSame(1.5, $lastEvent->availableTonnes());
        $this->assertTrue($creditPurchase->hasNotified(CreditAlertThreshold::Warning));
    }

    public function testReachThresholdMarksLowerThresholdsAsNotified(): void
    {
        $creditPurchase = CreditPurchaseMother::validated(tonnes: 10.0);

        $creditPurchase->reachThreshold(CreditAlertThreshold::Depleted, 10.0, 0.0, 0.0);

        $this->assertSame(
            [CreditAlertThreshold::Warning, CreditAlertThreshold::High, CreditAlertThreshold::Depleted],
            $creditPurchase->notifiedThresholds(),
        );
    }

    public function testReachThresholdDoesNotRepeatANotifiedThreshold(): void
    {
        $creditPurchase = CreditPurchaseMother::validated(tonnes: 10.0);

        $creditPurchase->reachThreshold(CreditAlertThreshold::High, 9.0, 1.0, 1.0);
        $eventsAfterFirst = count($this->spySubscriber->events());

        $creditPurchase->reachThreshold(CreditAlertThreshold::High, 9.5, 0.5, 0.5);
        $creditPurchase->reachThreshold(CreditAlertThreshold::Warning, 9.5, 0.5, 0.5);

        $this->assertCount($eventsAfterFirst, $this->spySubscriber->events());
    }

    public function testReachThresholdDoesNothingUnlessValidated(): void
    {
        $creditPurchase = CreditPurchaseMother::pending();

        $creditPurchase->reachThreshold(CreditAlertThreshold::Depleted, 10.0, 0.0, 0.0);

        $this->assertSame([], $creditPurchase->notifiedThresholds());
        $this->assertNotInstanceOf(CreditThresholdReached::class, $this->spySubscriber->lastEvent());
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
