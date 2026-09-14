<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\ChangelogEntry;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ChangelogEntryAuthorizerTest extends TestCase
{
    public function testAdminsCanListViewAndAdminister(): void
    {
        $sut = new ChangelogEntryAuthorizer();

        $entry = ChangelogEntryMother::any();
        $admin = UserMother::adminUser();

        $this->assertTrue($sut->canDoThat($entry, Action::List, $admin));
        $this->assertTrue($sut->canDoThat($entry, Action::View, $admin));
        $this->assertTrue($sut->canDoThat($entry, Action::Admin, $admin));
    }

    public function testSuperAdminsCanListViewAndAdminister(): void
    {
        $sut = new ChangelogEntryAuthorizer();

        $entry = ChangelogEntryMother::any();
        $superAdmin = UserMother::superAdminUser();

        $this->assertTrue($sut->canDoThat($entry, Action::List, $superAdmin));
        $this->assertTrue($sut->canDoThat($entry, Action::View, $superAdmin));
        $this->assertTrue($sut->canDoThat($entry, Action::Admin, $superAdmin));
    }

    public function testOwnersAndUsersCannotReachTheChangelog(): void
    {
        $sut = new ChangelogEntryAuthorizer();

        $entry = ChangelogEntryMother::any();
        $owner = UserMother::ownerUser();
        $user = UserMother::fromUsernameAndRole('user@dediez.software', Role::User);

        $this->assertFalse($sut->canDoThat($entry, Action::List, $owner));
        $this->assertFalse($sut->canDoThat($entry, Action::View, $owner));
        $this->assertFalse($sut->canDoThat($entry, Action::List, $user));
        $this->assertFalse($sut->canDoThat($entry, Action::View, $user));
    }

    public function testOnlySuperAdminsCanViewTheTechnicalManual(): void
    {
        $sut = new ChangelogEntryAuthorizer();

        $entry = ChangelogEntryMother::any();
        $superAdmin = UserMother::superAdminUser();
        $admin = UserMother::adminUser();
        $owner = UserMother::ownerUser();

        $this->assertTrue($sut->canDoThat($entry, Action::History, $superAdmin));
        $this->assertFalse($sut->canDoThat($entry, Action::History, $admin));
        $this->assertFalse($sut->canDoThat($entry, Action::History, $owner));
    }

    public function testItOnlySupportsChangelogEntries(): void
    {
        $sut = new ChangelogEntryAuthorizer();

        $this->assertTrue($sut->supports(ChangelogEntryMother::any()));
        $this->assertFalse($sut->supports(UserMother::adminUser()));
    }
}
