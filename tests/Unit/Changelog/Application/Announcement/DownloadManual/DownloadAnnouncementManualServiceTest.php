<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\DownloadManual;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\DownloadManual\DownloadAnnouncementManualRequest;
use App\Changelog\Application\Announcement\DownloadManual\DownloadAnnouncementManualService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Changelog\Infrastructure\Storage\InMemoryAnnouncementManualStorage;
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
    private InMemoryAnnouncementManualStorage $storage;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->storage = new InMemoryAnnouncementManualStorage();
    }

    public function testReturnsTheManual(): void
    {
        $announcement = AnnouncementMother::withManual(Locale::Spanish);
        $announcement->publish(false);
        $this->storage->add((string) $announcement->manualPath(Locale::Spanish), '%PDF-1.4 manual');

        $response = $this->sut($announcement)(new DownloadAnnouncementManualRequest($announcement->id(), Locale::Spanish), $this->user());

        $this->assertSame('%PDF-1.4 manual', stream_get_contents($response->stream));
        $this->assertSame('manual.pdf', $response->fileName);
        $this->assertSame('application/pdf', $response->mimeType);
        $this->assertSame(strlen('%PDF-1.4 manual'), $response->size);
    }

    public function testATranslationWithoutManual(): void
    {
        $announcement = AnnouncementMother::published();

        $this->expectException(ManualNotFoundException::class);

        $this->sut($announcement)(new DownloadAnnouncementManualRequest($announcement->id(), Locale::Spanish), $this->user());
    }

    public function testAManualMissingFromTheStorage(): void
    {
        $announcement = AnnouncementMother::withManual(Locale::Spanish);
        $announcement->publish(false);

        $this->expectException(ManualNotFoundException::class);

        $this->sut($announcement)(new DownloadAnnouncementManualRequest($announcement->id(), Locale::Spanish), $this->user());
    }

    public function testUsersCannotDownloadTheManualOfADraft(): void
    {
        $announcement = AnnouncementMother::withManual(Locale::Spanish);
        $this->storage->add((string) $announcement->manualPath(Locale::Spanish), '%PDF-1.4 manual');

        $this->expectException(UnauthorizedActionException::class);

        $this->sut($announcement)(new DownloadAnnouncementManualRequest($announcement->id(), Locale::Spanish), $this->user());
    }

    private function sut(Announcement $announcement): DownloadAnnouncementManualService
    {
        return new DownloadAnnouncementManualService(
            AnnouncementRepositoryMother::withAnnouncements($announcement),
            $this->storage,
            new AnnouncementAuthorizer(),
        );
    }

    private function user(): User
    {
        return UserMother::fromUsernameAndRole('user@dediez.software', Role::User);
    }
}
