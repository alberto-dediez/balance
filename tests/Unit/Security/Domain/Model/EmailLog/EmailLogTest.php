<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\EmailLog;

use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogBounced;
use App\Security\Domain\Model\EmailLog\EmailLogClicked;
use App\Security\Domain\Model\EmailLog\EmailLogDeferred;
use App\Security\Domain\Model\EmailLog\EmailLogDelivered;
use App\Security\Domain\Model\EmailLog\EmailLogDropped;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogOpened;
use App\Security\Domain\Model\EmailLog\EmailLogRegistered;
use App\Security\Domain\Model\EmailLog\EmailLogRemoved;
use App\Security\Domain\Model\EmailLog\EmailLogSpamReported;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class EmailLogTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testRegisterAsSentRecordsEmailLogRegistered(): void
    {
        $emailLog = EmailLogMother::sent('someone@example.com');

        $this->assertEquals(EmailLogStatus::Sent, $emailLog->status());
        $this->assertEquals('someone@example.com', $emailLog->recipientEmail());
        $this->assertEquals('provider-message-id', $emailLog->providerMessageId());
        $this->assertNull($emailLog->statusReason());
        $this->assertTrue($emailLog->isTrackable());
        $this->assertInstanceOf(EmailLogRegistered::class, $this->spy->lastEvent());
    }

    public function testRegisterAsFailedStoresReason(): void
    {
        $emailLog = EmailLogMother::failed('transport down');

        $this->assertEquals(EmailLogStatus::Failed, $emailLog->status());
        $this->assertEquals('transport down', $emailLog->statusReason());
        $this->assertNull($emailLog->providerMessageId());
    }

    public function testRegisterWithNonInitialStatusThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EmailLogMother::from(status: EmailLogStatus::Delivered);
    }

    public function testMarkAsDeliveredFromSent(): void
    {
        $emailLog = EmailLogMother::sent();
        $at = new \DateTimeImmutable('2026-10-20 10:00:00');

        $emailLog->markAsDelivered($at);

        $this->assertEquals(EmailLogStatus::Delivered, $emailLog->status());
        $this->assertEquals($at, $emailLog->deliveredAt());
        $this->assertEquals($at, $emailLog->lastEventAt());
        $this->assertInstanceOf(EmailLogDelivered::class, $this->spy->lastEvent());
    }

    public function testMarkAsDeliveredFromDeferredClearsReason(): void
    {
        $emailLog = EmailLogMother::sent();
        $emailLog->markAsDeferred('mailbox full', new \DateTimeImmutable('NOW'));

        $emailLog->markAsDelivered(new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Delivered, $emailLog->status());
        $this->assertNull($emailLog->statusReason());
    }

    public function testMarkAsDeferredFromSent(): void
    {
        $emailLog = EmailLogMother::sent();

        $emailLog->markAsDeferred('mailbox full', new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Deferred, $emailLog->status());
        $this->assertEquals('mailbox full', $emailLog->statusReason());
        $this->assertInstanceOf(EmailLogDeferred::class, $this->spy->lastEvent());
    }

    public function testMarkAsDeferredWhenDeferredUpdatesReason(): void
    {
        $emailLog = EmailLogMother::sent();
        $emailLog->markAsDeferred('first', new \DateTimeImmutable('NOW'));

        $emailLog->markAsDeferred('second', new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Deferred, $emailLog->status());
        $this->assertEquals('second', $emailLog->statusReason());
    }

    public function testMarkAsBouncedFromSent(): void
    {
        $emailLog = EmailLogMother::sent();

        $emailLog->markAsBounced('unknown user', new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Bounced, $emailLog->status());
        $this->assertEquals('unknown user', $emailLog->statusReason());
        $this->assertInstanceOf(EmailLogBounced::class, $this->spy->lastEvent());
    }

    public function testMarkAsBouncedFromDeferred(): void
    {
        $emailLog = EmailLogMother::sent();
        $emailLog->markAsDeferred('mailbox full', new \DateTimeImmutable('NOW'));

        $emailLog->markAsBounced('unknown user', new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Bounced, $emailLog->status());
    }

    public function testMarkAsBouncedAfterDeliveredKeepsDeliveredAt(): void
    {
        $emailLog = EmailLogMother::sent();
        $deliveredAt = new \DateTimeImmutable('2026-10-20 10:00:00');
        $emailLog->markAsDelivered($deliveredAt);

        $emailLog->markAsBounced('async bounce', new \DateTimeImmutable('2026-10-20 11:00:00'));

        $this->assertEquals(EmailLogStatus::Bounced, $emailLog->status());
        $this->assertEquals($deliveredAt, $emailLog->deliveredAt());
    }

    public function testMarkAsDroppedFromSent(): void
    {
        $emailLog = EmailLogMother::sent();

        $emailLog->markAsDropped('invalid', new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Dropped, $emailLog->status());
        $this->assertEquals('invalid', $emailLog->statusReason());
        $this->assertInstanceOf(EmailLogDropped::class, $this->spy->lastEvent());
    }

    public function testMarkAsDroppedFromDeferred(): void
    {
        $emailLog = EmailLogMother::sent();
        $emailLog->markAsDeferred('mailbox full', new \DateTimeImmutable('NOW'));

        $emailLog->markAsDropped('invalid', new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Dropped, $emailLog->status());
    }

    public function testDeliveredIgnoresDeliveredDeferredAndDropped(): void
    {
        $emailLog = EmailLogMother::delivered();
        $deliveredAt = $emailLog->deliveredAt();
        $eventsBefore = count($this->spy->events());

        $emailLog->markAsDelivered(new \DateTimeImmutable('+1 hour'));
        $emailLog->markAsDeferred('late', new \DateTimeImmutable('NOW'));
        $emailLog->markAsDropped('late', new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Delivered, $emailLog->status());
        $this->assertEquals($deliveredAt, $emailLog->deliveredAt());
        $this->assertNull($emailLog->statusReason());
        $this->assertCount($eventsBefore, $this->spy->events());
    }

    /**
     * @return array<string, array{0: callable(EmailLog): void}>
     */
    public static function terminalStates(): array
    {
        return [
            'bounced' => [fn(EmailLog $emailLog) => $emailLog->markAsBounced('bounce', new \DateTimeImmutable('NOW'))],
            'dropped' => [fn(EmailLog $emailLog) => $emailLog->markAsDropped('drop', new \DateTimeImmutable('NOW'))],
        ];
    }

    /**
     * @dataProvider terminalStates
     *
     * @param callable(EmailLog): void $toTerminal
     */
    public function testTerminalStatesIgnoreDeliveryEvents(callable $toTerminal): void
    {
        $emailLog = EmailLogMother::sent();
        $toTerminal($emailLog);
        $status = $emailLog->status();
        $eventsBefore = count($this->spy->events());

        $emailLog->markAsDelivered(new \DateTimeImmutable('NOW'));
        $emailLog->markAsDeferred('x', new \DateTimeImmutable('NOW'));
        $emailLog->markAsBounced('x', new \DateTimeImmutable('NOW'));
        $emailLog->markAsDropped('x', new \DateTimeImmutable('NOW'));

        $this->assertEquals($status, $emailLog->status());
        $this->assertCount($eventsBefore, $this->spy->events());
    }

    public function testFailedIgnoresDeliveryEvents(): void
    {
        $emailLog = EmailLogMother::failed('transport down');
        $eventsBefore = count($this->spy->events());

        $emailLog->markAsDelivered(new \DateTimeImmutable('NOW'));
        $emailLog->markAsDeferred('x', new \DateTimeImmutable('NOW'));
        $emailLog->markAsBounced('x', new \DateTimeImmutable('NOW'));
        $emailLog->markAsDropped('x', new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Failed, $emailLog->status());
        $this->assertEquals('transport down', $emailLog->statusReason());
        $this->assertCount($eventsBefore, $this->spy->events());
    }

    public function testRegisterOpenTwice(): void
    {
        $emailLog = EmailLogMother::sent();
        $first = new \DateTimeImmutable('2026-10-20 10:00:00');
        $second = new \DateTimeImmutable('2026-10-20 12:00:00');

        $emailLog->registerOpen($first);
        $emailLog->registerOpen($second);

        $this->assertEquals(2, $emailLog->openCount());
        $this->assertEquals($first, $emailLog->firstOpenedAt());
        $this->assertEquals($second, $emailLog->lastOpenedAt());
        $this->assertTrue($emailLog->hasBeenOpened());
        $this->assertInstanceOf(EmailLogOpened::class, $this->spy->lastEvent());
    }

    public function testRegisterClickTwice(): void
    {
        $emailLog = EmailLogMother::sent();
        $first = new \DateTimeImmutable('2026-10-20 10:00:00');

        $emailLog->registerClick($first);
        $emailLog->registerClick(new \DateTimeImmutable('2026-10-20 12:00:00'));

        $this->assertEquals(2, $emailLog->clickCount());
        $this->assertEquals($first, $emailLog->firstClickedAt());
        $this->assertInstanceOf(EmailLogClicked::class, $this->spy->lastEvent());
    }

    public function testRegisterOpenWhenBouncedCounts(): void
    {
        $emailLog = EmailLogMother::sent();
        $emailLog->markAsBounced('bounce', new \DateTimeImmutable('NOW'));

        $emailLog->registerOpen(new \DateTimeImmutable('NOW'));

        $this->assertEquals(EmailLogStatus::Bounced, $emailLog->status());
        $this->assertEquals(1, $emailLog->openCount());
    }

    public function testMarkAsSpamReported(): void
    {
        $emailLog = EmailLogMother::delivered();
        $at = new \DateTimeImmutable('2026-10-20 10:00:00');

        $emailLog->markAsSpamReported($at);

        $this->assertEquals(EmailLogStatus::Delivered, $emailLog->status());
        $this->assertEquals($at, $emailLog->spamReportedAt());
        $this->assertInstanceOf(EmailLogSpamReported::class, $this->spy->lastEvent());
    }

    public function testRemoveRecordsEmailLogRemoved(): void
    {
        $emailLog = EmailLogMother::sent(id: new EmailLogId('0199f3b2-0000-7000-8000-000000000001'));

        $emailLog->remove();

        $lastEvent = $this->spy->lastEvent();
        $this->assertInstanceOf(EmailLogRemoved::class, $lastEvent);
        $this->assertEquals('0199f3b2-0000-7000-8000-000000000001', (string) $lastEvent->aggregateRootId());
    }
}
