<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert;

use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertMother;
use App\Waste\Application\Alert\AlertAuthorizer;
use PHPUnit\Framework\TestCase;

class AlertAuthorizerTest extends TestCase
{
    public function testAdminCanListAlerts(): void
    {
        $user = UserMother::adminUser();
        $subject = AlertMother::detectedAlert();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanListAlertsFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = AlertMother::fromIdAndAccount((string) \App\Waste\Domain\Model\Alert\AlertId::generate(), 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCannotListAlertsFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = AlertMother::fromIdAndAccount((string) \App\Waste\Domain\Model\Alert\AlertId::generate(), 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = AlertMother::detectedAlert();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = AlertMother::detectedAlert();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOnlyAdminCanRemove(): void
    {
        $admin = UserMother::adminUser();
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = AlertMother::detectedAlert();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $admin));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $owner));
    }

    private function createSUT(): AlertAuthorizer
    {
        return new AlertAuthorizer();
    }
}
