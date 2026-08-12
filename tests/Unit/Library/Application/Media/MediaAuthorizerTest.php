<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media;

use App\Library\Domain\Model\Media\MediaVisibility;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MediaAuthorizerTest extends TestCase
{
    public function testEveryUserCanListAndUpload(): void
    {
        $sut = MediaAuthorizerMother::create();

        $media = MediaMother::uploadedMedia();

        $user = UserMother::fromUsernameAndRole('user@dediez.software', Role::User);

        $this->assertTrue($sut->canDoThat($media, Action::List, $user));
        $this->assertTrue($sut->canDoThat($media, Action::Register, $user));
    }

    public function testAdminsCanEditAnyMediaOfTheirAccount(): void
    {
        $sut = MediaAuthorizerMother::create();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);

        $admin = UserMother::fromUsernameAndRoleAndAccount('admin@dediez.software', Role::Admin, $accountId);

        $this->assertTrue($sut->canDoThat($media, Action::Edit, $admin));
        $this->assertTrue($sut->canDoThat($media, Action::Remove, $admin));
    }

    public function testAdminsCannotReachTheMediaOfAnotherAccount(): void
    {
        $sut = MediaAuthorizerMother::create();

        $media = MediaMother::fromNameAndAccount('logo.png', AccountId::generate());

        $admin = UserMother::fromUsernameAndRoleAndAccount('admin@dediez.software', Role::Admin, AccountId::generate());

        $this->assertFalse($sut->canDoThat($media, Action::View, $admin));
        $this->assertFalse($sut->canDoThat($media, Action::Edit, $admin));
    }

    public function testUsersWithoutAccountOnlyReachTheMediaWithoutAccount(): void
    {
        $sut = MediaAuthorizerMother::create();

        $admin = UserMother::adminUser();

        $this->assertTrue($sut->canDoThat(MediaMother::uploadedMedia(), Action::View, $admin));
        $this->assertFalse($sut->canDoThat(MediaMother::fromNameAndAccount('logo.png', AccountId::generate()), Action::View, $admin));
    }

    public function testUsersWithAccountCannotReachTheMediaWithoutAccount(): void
    {
        $sut = MediaAuthorizerMother::create();

        $user = UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, AccountId::generate());

        $this->assertFalse($sut->canDoThat(MediaMother::uploadedMedia(), Action::View, $user));
    }

    public function testOwnersCanEditTheMediaOfTheirAccount(): void
    {
        $sut = MediaAuthorizerMother::create();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId);
        $foreignOwner = UserMother::fromUsernameAndRoleAndAccount('foreign@dediez.software', Role::Owner, AccountId::generate());

        $this->assertTrue($sut->canDoThat($media, Action::Edit, $owner));
        $this->assertFalse($sut->canDoThat($media, Action::Edit, $foreignOwner));
    }

    public function testUsersOnlyEditWhatTheyUploaded(): void
    {
        $sut = MediaAuthorizerMother::create();

        $user = UserMother::fromUsernameAndRole('user@dediez.software', Role::User);
        $anotherUser = UserMother::fromUsernameAndRole('another@dediez.software', Role::User);

        $media = MediaMother::fromNameAndUploader('logo.png', $user->id());

        $this->assertTrue($sut->canDoThat($media, Action::Edit, $user));
        $this->assertFalse($sut->canDoThat($media, Action::Edit, $anotherUser));
    }

    public function testMediaOfAnotherAccountCannotBeViewed(): void
    {
        $sut = MediaAuthorizerMother::create();

        $media = MediaMother::fromNameAndAccount('logo.png', AccountId::generate());

        $foreignUser = UserMother::fromUsernameAndRoleAndAccount('foreign@dediez.software', Role::User, AccountId::generate());

        $this->assertFalse($sut->canDoThat($media, Action::View, $foreignUser));
    }

    public function testSharedMediaIsReachedByAnyUser(): void
    {
        $sut = MediaAuthorizerMother::create();

        $foreignUser = UserMother::fromUsernameAndRoleAndAccount('foreign@dediez.software', Role::User, AccountId::generate());

        $systemMedia = MediaMother::fromNameAndVisibility('manual.pdf', MediaVisibility::System, AccountId::generate());
        $publicMedia = MediaMother::fromNameAndVisibility('terms.pdf', MediaVisibility::Public, AccountId::generate());

        $this->assertTrue($sut->canDoThat($systemMedia, Action::View, $foreignUser));
        $this->assertTrue($sut->canDoThat($publicMedia, Action::View, $foreignUser));
    }

    public function testSharedMediaIsOnlyEditedWithinTheAccountItBelongsTo(): void
    {
        $sut = MediaAuthorizerMother::create();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndVisibility('manual.pdf', MediaVisibility::System, $accountId);

        $admin = UserMother::fromUsernameAndRoleAndAccount('admin@dediez.software', Role::Admin, $accountId);
        $foreignAdmin = UserMother::fromUsernameAndRoleAndAccount('foreign@dediez.software', Role::Admin, AccountId::generate());

        $this->assertTrue($sut->canDoThat($media, Action::Edit, $admin));
        $this->assertFalse($sut->canDoThat($media, Action::Edit, $foreignAdmin));
        $this->assertFalse($sut->canDoThat($media, Action::Remove, $foreignAdmin));
    }

    public function testOnlyTheAdminsOfTheAccountAdministerTheMedia(): void
    {
        $sut = MediaAuthorizerMother::create();

        $accountId = AccountId::generate();

        $media = MediaMother::fromNameAndAccount('logo.png', $accountId);

        $admin = UserMother::fromUsernameAndRoleAndAccount('admin@dediez.software', Role::Admin, $accountId);
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $accountId);
        $foreignAdmin = UserMother::fromUsernameAndRoleAndAccount('foreign@dediez.software', Role::Admin, AccountId::generate());

        $this->assertTrue($sut->canDoThat($media, Action::Admin, $admin));
        $this->assertFalse($sut->canDoThat($media, Action::Admin, $owner));
        $this->assertFalse($sut->canDoThat($media, Action::Admin, $user));
        $this->assertFalse($sut->canDoThat($media, Action::Admin, $foreignAdmin));
    }

    public function testItOnlySupportsMedia(): void
    {
        $sut = MediaAuthorizerMother::create();

        $this->assertTrue($sut->supports(MediaMother::uploadedMedia()));
        $this->assertFalse($sut->supports(UserMother::adminUser()));
    }
}
