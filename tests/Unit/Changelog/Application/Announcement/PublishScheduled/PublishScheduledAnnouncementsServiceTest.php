<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\PublishScheduled;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Notify\NotifyAnnouncementRequest;
use App\Changelog\Application\Announcement\PublishScheduled\PublishScheduledAnnouncementsRequest;
use App\Changelog\Application\Announcement\PublishScheduled\PublishScheduledAnnouncementsService;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Send\SpyDeferredCommandHandler;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class PublishScheduledAnnouncementsServiceTest extends TestCase
{
    private SpyDeferredCommandHandler $deferred;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();
        $this->deferred = new SpyDeferredCommandHandler();
        CommandBusDispatcher::instance()->subscribe($this->deferred);
    }

    public function testPublishesOnlyTheDueAnnouncementsAndQueuesTheirNotification(): void
    {
        $due = AnnouncementMother::scheduledFor('2026-01-01 10:00', true, 'Vencida');
        $future = AnnouncementMother::scheduledFor('2030-01-01 10:00', true, 'Futura');
        $draft = AnnouncementMother::draft('Borrador');

        $sut = new PublishScheduledAnnouncementsService(AnnouncementRepositoryMother::withAnnouncements($due, $future, $draft), new AnnouncementAuthorizer());

        $response = $sut(new PublishScheduledAnnouncementsRequest(new \DateTimeImmutable('2026-06-01 00:00')), UserMother::adminUser());

        $this->assertSame(1, $response->published);
        $this->assertSame(1, $response->notified);
        $this->assertTrue($due->isPublished());
        $this->assertSame(AnnouncementStatus::Scheduled, $future->status());
        $this->assertSame(AnnouncementStatus::Draft, $draft->status());
        $this->assertCount(1, $this->deferred->commands());

        $command = $this->deferred->commands()[0]->command();

        $this->assertInstanceOf(NotifyAnnouncementRequest::class, $command);
        $this->assertTrue($due->id()->equals($command->announcementId));
    }

    public function testPublishesWithoutNotificationWhenNotRequested(): void
    {
        $due = AnnouncementMother::scheduledFor('2026-01-01 10:00', false);

        $sut = new PublishScheduledAnnouncementsService(AnnouncementRepositoryMother::withAnnouncements($due), new AnnouncementAuthorizer());

        $response = $sut(new PublishScheduledAnnouncementsRequest(new \DateTimeImmutable('2026-06-01 00:00')), UserMother::adminUser());

        $this->assertSame(1, $response->published);
        $this->assertSame(0, $response->notified);
        $this->assertTrue($due->isPublished());
        $this->assertCount(0, $this->deferred->commands());
    }

    public function testPublishesWithoutNotificationWhenAnnouncementsAreDisabled(): void
    {
        $due = AnnouncementMother::scheduledFor('2026-01-01 10:00', true);

        $sut = new PublishScheduledAnnouncementsService(AnnouncementRepositoryMother::withAnnouncements($due), new AnnouncementAuthorizer(), announcementsEnabled: false);

        $response = $sut(new PublishScheduledAnnouncementsRequest(new \DateTimeImmutable('2026-06-01 00:00')), UserMother::adminUser());

        $this->assertSame(1, $response->published);
        $this->assertSame(0, $response->notified);
        $this->assertTrue($due->isPublished());
        $this->assertCount(0, $this->deferred->commands());
    }

    public function testWithoutScheduledAnnouncements(): void
    {
        $sut = new PublishScheduledAnnouncementsService(AnnouncementRepositoryMother::inMemory(), new AnnouncementAuthorizer());

        $response = $sut(new PublishScheduledAnnouncementsRequest(), UserMother::adminUser());

        $this->assertSame(0, $response->published);
        $this->assertSame(0, $response->notified);
    }

    public function testOwnersCannotPublishScheduledAnnouncements(): void
    {
        $sut = new PublishScheduledAnnouncementsService(AnnouncementRepositoryMother::inMemory(), new AnnouncementAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new PublishScheduledAnnouncementsRequest(), UserMother::ownerUser());
    }
}
