<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\List;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\List\ListAnnouncementsRequest;
use App\Changelog\Application\Announcement\List\ListAnnouncementsService;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListAnnouncementsServiceTest extends TestCase
{
    private ListAnnouncementsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->sut = new ListAnnouncementsService(
            AnnouncementRepositoryMother::withAnnouncements(
                AnnouncementMother::draft('Borrador'),
                AnnouncementMother::scheduledFor('2030-01-01 10:00', false, 'Programada'),
                AnnouncementMother::published('Publicada A'),
                AnnouncementMother::published('Publicada B'),
            ),
            new AnnouncementAuthorizer(),
        );
    }

    public function testUsersOnlyReceivePublishedAnnouncements(): void
    {
        $user = UserMother::fromUsernameAndRole('user@dediez.software', Role::User);

        $response = ($this->sut)(new ListAnnouncementsRequest(), $user);

        $this->assertCount(2, $response->announcements);
        $this->assertSame(2, $response->totalResults);

        $response = ($this->sut)(new ListAnnouncementsRequest(status: AnnouncementStatus::Draft), $user);

        $this->assertSame(2, $response->totalResults);

        foreach ($response->announcements as $announcement) {
            $this->assertTrue($announcement->isPublished());
        }
    }

    public function testAdminsReceiveEverythingAndCanFilterByStatus(): void
    {
        $response = ($this->sut)(new ListAnnouncementsRequest(), UserMother::adminUser());

        $this->assertSame(4, $response->totalResults);

        $response = ($this->sut)(new ListAnnouncementsRequest(status: AnnouncementStatus::Scheduled), UserMother::adminUser());

        $this->assertSame(1, $response->totalResults);
        $this->assertSame('Programada', $response->announcements[0]->title());
    }
}
