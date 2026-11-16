<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionExpired;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFailed;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionImportedAsPaid;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionPaid;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionPaymentUnreconciled;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRegistered;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ProjectContributionTest extends TestCase
{
    public function testRegisteringRecordsTheBreakdown(): void
    {
        $contribution = ProjectContributionMother::pending(tonnes: 2.0);

        $this->assertSame(ProjectContributionStatus::Pending, $contribution->status());
        $this->assertSame(2.0, $contribution->tonnes());
        $this->assertSame(40.0, $contribution->taxableAmount());
        $this->assertSame(48.4, $contribution->totalAmount());
        $this->assertInstanceOf(ProjectContributionRegistered::class, array_values($contribution->recordedEvents())[0]);
    }

    public function testRegisteringWithoutCreditAccountUsesTheOwnAccount(): void
    {
        $accountId = AccountId::generate();

        $contribution = ProjectContributionMother::pending($accountId);

        $this->assertNotNull($contribution->creditAccountId());
        $this->assertTrue($accountId->equals($contribution->creditAccountId()));
        $this->assertFalse($contribution->usesSharedCredit());
    }

    public function testRegisteringWithAnotherCreditAccountUsesSharedCredit(): void
    {
        $accountId = AccountId::generate();
        $distributorAccountId = AccountId::generate();

        $contribution = ProjectContributionMother::pendingWithSharedCredit($accountId, $distributorAccountId);

        $this->assertNotNull($contribution->accountId());
        $this->assertNotNull($contribution->creditAccountId());
        $this->assertTrue($accountId->equals($contribution->accountId()));
        $this->assertTrue($distributorAccountId->equals($contribution->creditAccountId()));
        $this->assertTrue($contribution->usesSharedCredit());
    }

    public function testTheAllowedCreditPaymentTypeDependsOnHowTheContributionIsFunded(): void
    {
        $this->assertSame(CreditPurchasePaymentType::PaidByClient, ProjectContributionMother::ownCreditFunded()->allowedCreditPaymentType());
        $this->assertSame(
            CreditPurchasePaymentType::PaidByBioscore,
            ProjectContributionMother::paidWithSharedCredit(AccountId::generate(), AccountId::generate())->allowedCreditPaymentType(),
        );
        $this->assertNull(ProjectContributionMother::paid()->allowedCreditPaymentType());
    }

    public function testRebuildingARegisteredEventWithoutCreditAccountFallsBackToTheAccount(): void
    {
        $accountId = AccountId::generate();
        $event = array_values(ProjectContributionMother::pendingWithSharedCredit($accountId, AccountId::generate())->recordedEvents())[0];

        $this->assertInstanceOf(ProjectContributionRegistered::class, $event);

        $payload = $event->payload();
        unset($payload['creditAccountId']);

        $rebuilt = ProjectContributionRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $payload);

        $this->assertTrue($accountId->equals($rebuilt->creditAccountId()));
    }

    public function testRebuildingARegisteredEventKeepsTheCreditAccount(): void
    {
        $distributorAccountId = AccountId::generate();
        $event = array_values(ProjectContributionMother::pendingWithSharedCredit(AccountId::generate(), $distributorAccountId)->recordedEvents())[0];

        $this->assertInstanceOf(ProjectContributionRegistered::class, $event);

        $rebuilt = ProjectContributionRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertTrue($distributorAccountId->equals($rebuilt->creditAccountId()));
    }

    public function testRegisteringWithoutDateUsesTheCurrentMoment(): void
    {
        $before = new \DateTimeImmutable('NOW');

        $contribution = ProjectContributionMother::pending();

        $this->assertGreaterThanOrEqual($before, $contribution->registeredAt());
    }

    public function testRegisteringWithDateKeepsTheGivenMoment(): void
    {
        $registeredAt = new \DateTimeImmutable('2025-03-14 09:30:00');

        $contribution = ProjectContributionMother::imported(paidAt: $registeredAt);

        $this->assertEquals($registeredAt, $contribution->registeredAt());
    }

    public function testCannotRegisterWithoutTonnes(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProjectContributionMother::pending(tonnes: 0.0);
    }

    public function testPayingMarksItAsPaid(): void
    {
        $contribution = ProjectContributionMother::pending();
        $contribution->pay();

        $this->assertSame(ProjectContributionStatus::Paid, $contribution->status());
        $this->assertNotNull($contribution->paidAt());
        $this->assertInstanceOf(ProjectContributionPaid::class, array_values($contribution->recordedEvents())[1]);
    }

    public function testImportingAsPaidMarksItAsPaidOnTheGivenDate(): void
    {
        DomainEventPublisher::instance()->reset();
        $spySubscriber = new SpyDomainEventSubscriber();

        $paidAt = new \DateTimeImmutable('2025-03-14 09:30:00');
        $contribution = ProjectContributionMother::pending();
        $contribution->importAsPaid($paidAt);

        $this->assertTrue($contribution->isPaid());
        $this->assertEquals($paidAt, $contribution->paidAt());
        $this->assertEquals($paidAt, $contribution->resolvedAt());
        $this->assertInstanceOf(ProjectContributionImportedAsPaid::class, $spySubscriber->lastEvent());
        $this->assertSame([], array_filter(
            $spySubscriber->events(),
            static fn(DomainEvent $event): bool => $event instanceof ProjectContributionPaid,
        ));
    }

    public function testCannotImportAsPaidAResolvedContribution(): void
    {
        $contribution = ProjectContributionMother::paid();

        $this->expectException(\LogicException::class);

        $contribution->importAsPaid(new \DateTimeImmutable('2025-03-14'));
    }

    public function testPayingTwiceRecordsASingleEvent(): void
    {
        $contribution = ProjectContributionMother::pending();
        $contribution->pay();
        $contribution->pay();

        $this->assertCount(2, $contribution->recordedEvents());
    }

    public function testPayingAnExpiredContributionReportsTheMismatch(): void
    {
        $contribution = ProjectContributionMother::expired();
        $contribution->pay();

        $event = array_values($contribution->recordedEvents())[2];

        $this->assertSame(ProjectContributionStatus::Expired, $contribution->status());
        $this->assertInstanceOf(ProjectContributionPaymentUnreconciled::class, $event);
        $this->assertSame(ProjectContributionStatus::Expired, $event->status());
    }

    public function testAPaidContributionIsNeitherFailedNorExpiredAfterwards(): void
    {
        $contribution = ProjectContributionMother::paid();
        $contribution->fail('card_declined');
        $contribution->expire();

        $this->assertSame(ProjectContributionStatus::Paid, $contribution->status());
        $this->assertNull($contribution->failureReason());
    }

    public function testFailingKeepsTheReason(): void
    {
        $contribution = ProjectContributionMother::pending();
        $contribution->fail('card_declined');

        $this->assertSame(ProjectContributionStatus::Failed, $contribution->status());
        $this->assertSame('card_declined', $contribution->failureReason());
        $this->assertInstanceOf(ProjectContributionFailed::class, array_values($contribution->recordedEvents())[1]);
    }

    public function testExpiringResolvesTheContribution(): void
    {
        $contribution = ProjectContributionMother::pending();
        $contribution->expire();

        $this->assertSame(ProjectContributionStatus::Expired, $contribution->status());
        $this->assertInstanceOf(ProjectContributionExpired::class, array_values($contribution->recordedEvents())[1]);
    }

    public function testAPendingContributionReservesCreditUntilItExpires(): void
    {
        $now = new \DateTimeImmutable('2026-08-06 12:00:00');
        $contribution = ProjectContributionMother::pending(expiresAt: $now->modify('+1 hour'));

        $this->assertTrue($contribution->reservesCredit($now));
        $this->assertFalse($contribution->reservesCredit($now->modify('+2 hours')));
    }

    public function testAPaidContributionAlwaysReservesCredit(): void
    {
        $now = new \DateTimeImmutable('2026-08-06 12:00:00');
        $contribution = ProjectContributionMother::pending(expiresAt: $now->modify('-1 hour'));
        $contribution->pay();

        $this->assertTrue($contribution->reservesCredit($now));
    }

    public function testAnExpiredContributionReservesNothing(): void
    {
        $contribution = ProjectContributionMother::expired();

        $this->assertFalse($contribution->reservesCredit(new \DateTimeImmutable('NOW')));
    }

    public function testHasExpiredOnlyAppliesToPendingContributionsPastTheirWindow(): void
    {
        $now = new \DateTimeImmutable('2026-08-06 12:00:00');

        $this->assertTrue(ProjectContributionMother::pending(expiresAt: $now->modify('-1 hour'))->hasExpired($now));
        $this->assertFalse(ProjectContributionMother::pending(expiresAt: $now->modify('+1 hour'))->hasExpired($now));
        $this->assertFalse(ProjectContributionMother::paid()->hasExpired($now));
    }
}
