<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\View;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\View\ViewAnnouncementRequest;
use App\Changelog\Application\Announcement\View\ViewAnnouncementService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ViewAnnouncementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testReturnsTheTranslationOfTheCurrentLocale(): void
    {
        $announcement = AnnouncementMother::withTranslations(Locale::Spanish, Locale::English);
        $announcement->publish(false);

        $sut = $this->sut($announcement);

        FakeLocaleSetter::set(Locale::English);

        $response = $sut(new ViewAnnouncementRequest($announcement->id()), $this->user());

        $this->assertSame('Título en', $response->announcement->title());
    }

    public function testFallsBackToTheDefaultLocale(): void
    {
        $announcement = AnnouncementMother::withTranslations(Locale::Spanish);
        $announcement->publish(false);

        $sut = $this->sut($announcement);

        FakeLocaleSetter::set(Locale::French);

        $response = $sut(new ViewAnnouncementRequest($announcement->id()), $this->user());

        $this->assertSame('Título es', $response->announcement->title());
    }

    public function testUsersCannotViewADraft(): void
    {
        $announcement = AnnouncementMother::draft();

        $this->expectException(UnauthorizedActionException::class);

        $this->sut($announcement)(new ViewAnnouncementRequest($announcement->id()), $this->user());
    }

    public function testUsersCannotViewAScheduledAnnouncement(): void
    {
        $announcement = AnnouncementMother::scheduledFor('2030-01-01 10:00');

        $this->expectException(UnauthorizedActionException::class);

        $this->sut($announcement)(new ViewAnnouncementRequest($announcement->id()), $this->user());
    }

    public function testAdminsCanViewADraft(): void
    {
        $announcement = AnnouncementMother::draft('Borrador');

        $response = $this->sut($announcement)(new ViewAnnouncementRequest($announcement->id()), UserMother::adminUser());

        $this->assertSame('Borrador', $response->announcement->title());
    }

    public function testUnknownAnnouncement(): void
    {
        $this->expectException(AnnouncementNotFoundException::class);

        $this->sut(AnnouncementMother::published())(new ViewAnnouncementRequest(AnnouncementId::generate()), $this->user());
    }

    private function sut(Announcement $announcement): ViewAnnouncementService
    {
        return new ViewAnnouncementService(AnnouncementRepositoryMother::withAnnouncements($announcement), new AnnouncementAuthorizer());
    }

    private function user(): User
    {
        return UserMother::fromUsernameAndRole('user@dediez.software', Role::User);
    }
}
