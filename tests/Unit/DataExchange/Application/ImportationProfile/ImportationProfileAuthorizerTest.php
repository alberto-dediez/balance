<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\ImportationProfile;

use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\DataExchange\Domain\Model\ImportationProfile\ImportationProfileMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ImportationProfileAuthorizerTest extends TestCase
{
    public function testAdminCanDoAnything(): void
    {
        $authorizer = ImportationProfileAuthorizerMother::create();
        $profile = ImportationProfileMother::random();
        $user = UserMother::adminUser();

        $this->assertTrue($authorizer->canDoThat($profile, Action::View, $user));
        $this->assertTrue($authorizer->canDoThat($profile, Action::Edit, $user));
    }

    public function testOwnerCanAccessOwnAccountProfile(): void
    {
        $accountId = AccountId::generate();
        $authorizer = ImportationProfileAuthorizerMother::create();
        $profile = ImportationProfileMother::fromTypeAndAccount('measurements', $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertTrue($authorizer->canDoThat($profile, Action::View, $user));
        $this->assertTrue($authorizer->canDoThat($profile, Action::Edit, $user));
    }

    public function testOwnerCannotAccessOtherAccountProfile(): void
    {
        $authorizer = ImportationProfileAuthorizerMother::create();
        $profile = ImportationProfileMother::fromTypeAndAccount('measurements', AccountId::generate());
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->assertFalse($authorizer->canDoThat($profile, Action::View, $user));
        $this->assertFalse($authorizer->canDoThat($profile, Action::Edit, $user));
    }

    public function testOwnerCannotAccessGlobalProfile(): void
    {
        $authorizer = ImportationProfileAuthorizerMother::create();
        $profile = ImportationProfileMother::fromTypeAndAccount('measurements', null);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->assertFalse($authorizer->canDoThat($profile, Action::View, $user));
    }

    public function testSuperAdminCanAccessGlobalProfile(): void
    {
        $authorizer = ImportationProfileAuthorizerMother::create();
        $profile = ImportationProfileMother::fromTypeAndAccount('measurements', null);
        $user = UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin);

        $this->assertTrue($authorizer->canDoThat($profile, Action::View, $user));
    }
}
