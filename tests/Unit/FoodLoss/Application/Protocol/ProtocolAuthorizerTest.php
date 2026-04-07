<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Protocol;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ProtocolAuthorizerTest extends TestCase
{
    public function testOwnerCanListProtocolsFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCanListProtocolsFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCannotListProtocolsFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'other-account');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListProtocolsFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'other-account');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCanViewProtocolFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testOwnerCanRegisterProtocol(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testUserCannotRegisterProtocol(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditProtocolFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditProtocolFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'other-account');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testUserCannotEditProtocol(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveProtocolFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = ProtocolMother::from(name: 'Test', id: (string) ProtocolId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testAdminCannotListProtocols(): void
    {
        $user = UserMother::adminUser();
        $subject = new Protocol(ProtocolId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): ProtocolAuthorizer
    {
        return new ProtocolAuthorizer();
    }
}
