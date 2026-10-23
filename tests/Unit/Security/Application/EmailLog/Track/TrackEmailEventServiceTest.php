<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\EmailLog\Track;

use App\Security\Application\EmailLog\Track\EmailTrackingEventType;
use App\Security\Application\EmailLog\Track\TrackEmailEventRequest;
use App\Security\Application\EmailLog\Track\TrackEmailEventService;
use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogDelivered;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryEmailLogRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogMother;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class TrackEmailEventServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryEmailLogRepository $repository;

    private EmailLog $emailLog;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->repository = EmailLogRepositoryMother::inMemory();
        $this->emailLog = EmailLogMother::sent();
        $this->repository->add($this->emailLog);
    }

    public function testDelivered(): void
    {
        $response = $this->track(EmailTrackingEventType::Delivered);

        $this->assertTrue($response);
        $this->assertEquals(EmailLogStatus::Delivered, $this->stored()->status());
        $this->assertNotNull($this->stored()->deliveredAt());
    }

    public function testDeferred(): void
    {
        $this->track(EmailTrackingEventType::Deferred, 'mailbox full');

        $this->assertEquals(EmailLogStatus::Deferred, $this->stored()->status());
        $this->assertEquals('mailbox full', $this->stored()->statusReason());
    }

    public function testBounced(): void
    {
        $this->track(EmailTrackingEventType::Bounced, 'unknown user');

        $this->assertEquals(EmailLogStatus::Bounced, $this->stored()->status());
        $this->assertEquals('unknown user', $this->stored()->statusReason());
    }

    public function testDropped(): void
    {
        $this->track(EmailTrackingEventType::Dropped, 'invalid');

        $this->assertEquals(EmailLogStatus::Dropped, $this->stored()->status());
    }

    public function testOpened(): void
    {
        $this->track(EmailTrackingEventType::Opened);

        $this->assertEquals(1, $this->stored()->openCount());
        $this->assertNotNull($this->stored()->firstOpenedAt());
    }

    public function testClicked(): void
    {
        $this->track(EmailTrackingEventType::Clicked);

        $this->assertEquals(1, $this->stored()->clickCount());
        $this->assertNotNull($this->stored()->firstClickedAt());
    }

    public function testSpamReported(): void
    {
        $this->track(EmailTrackingEventType::SpamReported);

        $this->assertNotNull($this->stored()->spamReportedAt());
    }

    public function testUnknownIdIsNotTracked(): void
    {
        $sut = new TrackEmailEventService($this->repository);

        $response = $sut(new TrackEmailEventRequest(
            emailLogId: (string) EmailLogId::generate(),
            type: EmailTrackingEventType::Delivered,
            occurredOn: new \DateTimeImmutable('NOW'),
        ));

        $this->assertFalse($response->tracked);
        $this->assertEquals(EmailLogStatus::Sent, $this->stored()->status());
    }

    public function testRepeatedEventDoesNotDuplicateTransition(): void
    {
        $this->track(EmailTrackingEventType::Delivered);
        $this->track(EmailTrackingEventType::Delivered);

        $deliveredEvents = array_filter(
            $this->spySubscriber->events(),
            fn($event): bool => $event instanceof EmailLogDelivered,
        );

        $this->assertCount(1, $deliveredEvents);
    }

    public function testBounceWithoutReasonStoresEmptyString(): void
    {
        $this->track(EmailTrackingEventType::Bounced);

        $this->assertEquals(EmailLogStatus::Bounced, $this->stored()->status());
        $this->assertSame('', $this->stored()->statusReason());
    }

    private function track(EmailTrackingEventType $type, ?string $reason = null): bool
    {
        $sut = new TrackEmailEventService($this->repository);

        return $sut(new TrackEmailEventRequest(
            emailLogId: (string) $this->emailLog->id(),
            type: $type,
            occurredOn: new \DateTimeImmutable('NOW'),
            reason: $reason,
        ))->tracked;
    }

    private function stored(): EmailLog
    {
        $emailLog = $this->repository->find($this->emailLog->id());
        $this->assertNotNull($emailLog);

        return $emailLog;
    }
}
