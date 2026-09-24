<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\Remove;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Remove\RemoveAnnouncementRequest;
use App\Changelog\Application\Announcement\Remove\RemoveAnnouncementService;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\Announcement\AnnouncementRemoved;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveAnnouncementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRemovesTheAnnouncement(): void
    {
        $announcement = AnnouncementMother::published();
        $repository = AnnouncementRepositoryMother::withAnnouncements($announcement);
        $spy = new SpyDomainEventSubscriber();

        $sut = new RemoveAnnouncementService($repository, new AnnouncementAuthorizer());

        $sut(new RemoveAnnouncementRequest($announcement->id()), UserMother::adminUser());

        $this->assertInstanceOf(AnnouncementRemoved::class, $spy->lastEvent());
        $this->assertNull($repository->find($announcement->id()));
    }

    public function testUnknownAnnouncement(): void
    {
        $sut = new RemoveAnnouncementService(AnnouncementRepositoryMother::inMemory(), new AnnouncementAuthorizer());

        $this->expectException(AnnouncementNotFoundException::class);

        $sut(new RemoveAnnouncementRequest(AnnouncementId::generate()), UserMother::adminUser());
    }

    public function testOwnersCannotRemove(): void
    {
        $announcement = AnnouncementMother::published();

        $sut = new RemoveAnnouncementService(AnnouncementRepositoryMother::withAnnouncements($announcement), new AnnouncementAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RemoveAnnouncementRequest($announcement->id()), UserMother::ownerUser());
    }
}
