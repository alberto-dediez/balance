<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\Unpublish;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Unpublish\UnpublishAnnouncementRequest;
use App\Changelog\Application\Announcement\Unpublish\UnpublishAnnouncementService;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Domain\Model\Announcement\AnnouncementUnpublished;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UnpublishAnnouncementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUnpublishesAPublishedAnnouncement(): void
    {
        $announcement = AnnouncementMother::published();
        $spy = new SpyDomainEventSubscriber();

        $sut = new UnpublishAnnouncementService(AnnouncementRepositoryMother::withAnnouncements($announcement), new AnnouncementAuthorizer());

        $response = $sut(new UnpublishAnnouncementRequest($announcement->id()), UserMother::adminUser());

        $this->assertInstanceOf(AnnouncementUnpublished::class, $spy->lastEvent());
        $this->assertSame(AnnouncementStatus::Draft, $announcement->status());
        $this->assertFalse($response->wasScheduled);
    }

    public function testUnpublishingAScheduledAnnouncementCancelsTheSchedule(): void
    {
        $announcement = AnnouncementMother::scheduledFor('2030-01-01 10:00', true);
        $spy = new SpyDomainEventSubscriber();

        $sut = new UnpublishAnnouncementService(AnnouncementRepositoryMother::withAnnouncements($announcement), new AnnouncementAuthorizer());

        $response = $sut(new UnpublishAnnouncementRequest($announcement->id()), UserMother::adminUser());

        $this->assertInstanceOf(AnnouncementUnpublished::class, $spy->lastEvent());
        $this->assertSame(AnnouncementStatus::Draft, $announcement->status());
        $this->assertNull($announcement->scheduledAt());
        $this->assertTrue($response->wasScheduled);
    }

    public function testUnknownAnnouncement(): void
    {
        $sut = new UnpublishAnnouncementService(AnnouncementRepositoryMother::inMemory(), new AnnouncementAuthorizer());

        $this->expectException(AnnouncementNotFoundException::class);

        $sut(new UnpublishAnnouncementRequest(AnnouncementId::generate()), UserMother::adminUser());
    }

    public function testUsersCannotUnpublish(): void
    {
        $announcement = AnnouncementMother::published();

        $sut = new UnpublishAnnouncementService(AnnouncementRepositoryMother::withAnnouncements($announcement), new AnnouncementAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new UnpublishAnnouncementRequest($announcement->id()), UserMother::fromUsernameAndRole('user@dediez.software', Role::User));
    }
}
