<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\Publish;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Notify\NotifyAnnouncementRequest;
use App\Changelog\Application\Announcement\Publish\PublishAnnouncementRequest;
use App\Changelog\Application\Announcement\Publish\PublishAnnouncementService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementPublished;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Infrastructure\Persistence\InMemory\Repository\InMemoryAnnouncementRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Send\SpyDeferredCommandHandler;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class PublishAnnouncementServiceTest extends TestCase
{
    private SpyDeferredCommandHandler $deferred;

    private InMemoryAnnouncementRepository $repository;

    private PublishAnnouncementService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();
        $this->deferred = new SpyDeferredCommandHandler();
        CommandBusDispatcher::instance()->subscribe($this->deferred);
        $this->repository = AnnouncementRepositoryMother::inMemory();
        $this->sut = new PublishAnnouncementService($this->repository, new AnnouncementAuthorizer());
    }

    public function testPublishesNowWithoutNotifying(): void
    {
        $announcement = $this->given(AnnouncementMother::draft());

        $response = ($this->sut)(new PublishAnnouncementRequest($announcement->id()), UserMother::adminUser());

        $this->assertTrue($announcement->isPublished());
        $this->assertSame(AnnouncementStatus::Published, $response->status);
        $this->assertFalse($response->notified);
        $this->assertCount(0, $this->deferred->commands());
    }

    public function testPublishesNowAndQueuesTheNotification(): void
    {
        $announcement = $this->given(AnnouncementMother::draft());

        $response = ($this->sut)(new PublishAnnouncementRequest($announcement->id(), notifyUsers: true), UserMother::adminUser());

        $this->assertTrue($response->notified);
        $this->assertCount(1, $this->deferred->commands());

        $command = $this->deferred->commands()[0];

        $this->assertSame(Priority::LOW, $command->priority());
        $this->assertInstanceOf(NotifyAnnouncementRequest::class, $command->command());
        $this->assertTrue($announcement->id()->equals($command->command()->announcementId));
    }

    public function testDoesNotNotifyWhenAnnouncementsAreDisabled(): void
    {
        $announcement = $this->given(AnnouncementMother::draft());
        $sut = new PublishAnnouncementService($this->repository, new AnnouncementAuthorizer(), announcementsEnabled: false);

        $response = $sut(new PublishAnnouncementRequest($announcement->id(), notifyUsers: true), UserMother::adminUser());

        $this->assertTrue($announcement->isPublished());
        $this->assertFalse($response->notified);
        $this->assertFalse($announcement->notifyOnPublish());
        $this->assertCount(0, $this->deferred->commands());
    }

    public function testDoesNotScheduleTheNotificationWhenAnnouncementsAreDisabled(): void
    {
        $announcement = $this->given(AnnouncementMother::draft());
        $sut = new PublishAnnouncementService($this->repository, new AnnouncementAuthorizer(), announcementsEnabled: false);

        $sut(new PublishAnnouncementRequest(
            $announcement->id(),
            publishAt: new \DateTimeImmutable('2030-01-01 10:00'),
            notifyUsers: true,
            now: new \DateTimeImmutable('2026-01-01 10:00'),
        ), UserMother::adminUser());

        $this->assertFalse($announcement->notifyOnPublish());
    }

    public function testAPastDatePublishesNow(): void
    {
        $announcement = $this->given(AnnouncementMother::draft());

        ($this->sut)(new PublishAnnouncementRequest(
            $announcement->id(),
            publishAt: new \DateTimeImmutable('2020-01-01 10:00'),
            now: new \DateTimeImmutable('2026-01-01 10:00'),
        ), UserMother::adminUser());

        $this->assertTrue($announcement->isPublished());
    }

    public function testAFutureDateSchedulesWithoutNotifying(): void
    {
        $announcement = $this->given(AnnouncementMother::draft());

        $response = ($this->sut)(new PublishAnnouncementRequest(
            $announcement->id(),
            publishAt: new \DateTimeImmutable('2030-01-01 10:00'),
            notifyUsers: true,
            now: new \DateTimeImmutable('2026-01-01 10:00'),
        ), UserMother::adminUser());

        $this->assertSame(AnnouncementStatus::Scheduled, $response->status);
        $this->assertSame('2030-01-01 10:00', $response->scheduledAt?->format('Y-m-d H:i'));
        $this->assertFalse($announcement->isPublished());
        $this->assertTrue($announcement->notifyOnPublish());
        $this->assertCount(0, $this->deferred->commands());
    }

    public function testAnAlreadyPublishedAnnouncementIsLeftAsIs(): void
    {
        $announcement = $this->given(AnnouncementMother::published());
        $spy = new SpyDomainEventSubscriber();

        $response = ($this->sut)(new PublishAnnouncementRequest($announcement->id(), notifyUsers: true), UserMother::adminUser());

        $this->assertSame(AnnouncementStatus::Published, $response->status);
        $this->assertCount(0, array_filter($spy->events(), static fn($event): bool => $event instanceof AnnouncementPublished));
        $this->assertCount(0, $this->deferred->commands());
    }

    public function testOwnersCannotPublish(): void
    {
        $announcement = $this->given(AnnouncementMother::draft());

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new PublishAnnouncementRequest($announcement->id()), UserMother::ownerUser());
    }

    private function given(Announcement $announcement): Announcement
    {
        $this->repository->add($announcement);

        return $announcement;
    }
}
