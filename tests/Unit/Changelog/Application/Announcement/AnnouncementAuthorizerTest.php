<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AnnouncementAuthorizerTest extends TestCase
{
    public function testUsersCanListAndViewPublishedAnnouncements(): void
    {
        $sut = new AnnouncementAuthorizer();
        $user = UserMother::fromUsernameAndRole('user@dediez.software', Role::User);

        $this->assertTrue($sut->canDoThat(AnnouncementMother::published(), Action::List, $user));
        $this->assertTrue($sut->canDoThat(AnnouncementMother::published(), Action::View, $user));
    }

    public function testUsersCannotViewDraftsOrScheduledAnnouncements(): void
    {
        $sut = new AnnouncementAuthorizer();
        $user = UserMother::fromUsernameAndRole('user@dediez.software', Role::User);

        $this->assertFalse($sut->canDoThat(AnnouncementMother::draft(), Action::View, $user));
        $this->assertFalse($sut->canDoThat(AnnouncementMother::scheduledFor('2030-01-01 10:00'), Action::View, $user));
    }

    public function testAdminsCanViewDrafts(): void
    {
        $sut = new AnnouncementAuthorizer();

        $this->assertTrue($sut->canDoThat(AnnouncementMother::draft(), Action::View, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat(AnnouncementMother::scheduledFor('2030-01-01 10:00'), Action::View, UserMother::adminUser()));
    }

    public function testOwnersCannotManageAnnouncements(): void
    {
        $sut = new AnnouncementAuthorizer();
        $announcement = AnnouncementMother::draft();
        $owner = UserMother::ownerUser();

        $this->assertFalse($sut->canDoThat($announcement, Action::Register, $owner));
        $this->assertFalse($sut->canDoThat($announcement, Action::Edit, $owner));
        $this->assertFalse($sut->canDoThat($announcement, Action::Remove, $owner));
        $this->assertFalse($sut->canDoThat($announcement, Action::Admin, $owner));
    }

    public function testAdminsAndSuperAdminsCanDoEverything(): void
    {
        $sut = new AnnouncementAuthorizer();
        $announcement = AnnouncementMother::draft();

        foreach ([UserMother::adminUser(), UserMother::superAdminUser()] as $user) {
            foreach ([Action::List, Action::View, Action::Register, Action::Edit, Action::Remove, Action::Admin, Action::History] as $action) {
                $this->assertTrue($sut->canDoThat($announcement, $action, $user), $action->value);
            }
        }
    }

    public function testUsersAndOwnersCannotListOrViewWhenAnnouncementsAreDisabled(): void
    {
        $sut = new AnnouncementAuthorizer(announcementsEnabled: false);

        foreach ([UserMother::fromUsernameAndRole('user@dediez.software', Role::User), UserMother::ownerUser()] as $user) {
            $this->assertFalse($sut->canDoThat(AnnouncementMother::published(), Action::List, $user));
            $this->assertFalse($sut->canDoThat(AnnouncementMother::published(), Action::View, $user));
        }
    }

    public function testAdminsCanListAndViewWhenAnnouncementsAreDisabled(): void
    {
        $sut = new AnnouncementAuthorizer(announcementsEnabled: false);

        $this->assertTrue($sut->canDoThat(AnnouncementMother::published(), Action::List, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat(AnnouncementMother::published(), Action::View, UserMother::adminUser()));
    }

    public function testDoesNotSupportOtherSubjects(): void
    {
        $sut = new AnnouncementAuthorizer();

        $this->assertFalse($sut->supports(ChangelogEntryMother::any()));
        $this->assertFalse($sut->canDoThat(ChangelogEntryMother::any(), Action::List, UserMother::adminUser()));
    }
}
