<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Dashboard;

use App\FoodLoss\Application\Dashboard\GetUserDashboardRequest;
use App\FoodLoss\Application\Dashboard\UserDashboardAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UserDashboardAuthorizerTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id-test';

    public function testUserCanListDashboardForOwnAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId(self::ACCOUNT_ID));
        $subject = new GetUserDashboardRequest(accountId: new AccountId(self::ACCOUNT_ID));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListDashboardForOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId(self::ACCOUNT_ID));
        $subject = new GetUserDashboardRequest(accountId: new AccountId('other-account-id'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListDashboardWithoutAccountId(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId(self::ACCOUNT_ID));
        $subject = new GetUserDashboardRequest(accountId: null);

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCannotListDashboard(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID));
        $subject = new GetUserDashboardRequest(accountId: new AccountId(self::ACCOUNT_ID));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotListDashboard(): void
    {
        $user = UserMother::adminUser();
        $subject = new GetUserDashboardRequest(accountId: new AccountId(self::ACCOUNT_ID));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): UserDashboardAuthorizer
    {
        return new UserDashboardAuthorizer();
    }
}
