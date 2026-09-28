<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\DownloadManual;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\DownloadManual\DownloadAnnouncementManualRequest;
use App\Changelog\Application\Announcement\DownloadManual\DownloadAnnouncementManualService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DownloadAnnouncementManualServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testReturnsTheManual(): void
    {
        $announcement = AnnouncementMother::withManual(Locale::Spanish);
        $announcement->publish(false);

        $response = $this->sut($announcement)(new DownloadAnnouncementManualRequest($announcement->id(), Locale::Spanish), $this->user());

        $this->assertEquals($announcement->manualId(Locale::Spanish), $response->mediaId);
    }

    public function testATranslationWithoutManual(): void
    {
        $announcement = AnnouncementMother::published();

        $this->expectException(ManualNotFoundException::class);

        $this->sut($announcement)(new DownloadAnnouncementManualRequest($announcement->id(), Locale::Spanish), $this->user());
    }

    public function testUsersCannotDownloadTheManualOfADraft(): void
    {
        $announcement = AnnouncementMother::withManual(Locale::Spanish);

        $this->expectException(UnauthorizedActionException::class);

        $this->sut($announcement)(new DownloadAnnouncementManualRequest($announcement->id(), Locale::Spanish), $this->user());
    }

    private function sut(Announcement $announcement): DownloadAnnouncementManualService
    {
        return new DownloadAnnouncementManualService(
            AnnouncementRepositoryMother::withAnnouncements($announcement),
            new AnnouncementAuthorizer(),
        );
    }

    private function user(): User
    {
        return UserMother::fromUsernameAndRole('user@dediez.software', Role::User);
    }
}
