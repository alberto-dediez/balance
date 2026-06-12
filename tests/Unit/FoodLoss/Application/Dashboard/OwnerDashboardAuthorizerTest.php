<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Dashboard;

use App\FoodLoss\Application\Dashboard\GetOwnerDashboardRequest;
use App\FoodLoss\Application\Dashboard\OwnerDashboardAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class OwnerDashboardAuthorizerTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id-test';

    public function testOwnerCanListDashboardForOwnAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID));
        $subject = new GetOwnerDashboardRequest(accountId: new AccountId(self::ACCOUNT_ID));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCannotListDashboardForOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID));
        $subject = new GetOwnerDashboardRequest(accountId: new AccountId('other-account-id'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCannotListDashboardWithoutAccountId(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID));
        $subject = new GetOwnerDashboardRequest(accountId: null);

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotListDashboard(): void
    {
        $user = UserMother::adminUser();
        $subject = new GetOwnerDashboardRequest(accountId: new AccountId(self::ACCOUNT_ID));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListDashboard(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId(self::ACCOUNT_ID));
        $subject = new GetOwnerDashboardRequest(accountId: new AccountId(self::ACCOUNT_ID));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): OwnerDashboardAuthorizer
    {
        return new OwnerDashboardAuthorizer();
    }
}
