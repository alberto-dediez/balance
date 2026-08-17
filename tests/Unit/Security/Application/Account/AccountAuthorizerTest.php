<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AccountAuthorizerTest extends TestCase
{
    public function testAdminCanImpersonateActiveAccount(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat(AccountMother::registeredAccount(), Action::Impersonate, UserMother::adminUser());

        $this->assertTrue($result);
    }

    public function testSuperAdminCanImpersonateActiveAccount(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat(AccountMother::registeredAccount(), Action::Impersonate, UserMother::superAdminUser());

        $this->assertTrue($result);
    }

    public function testAdminCannotImpersonateInactiveAccount(): void
    {
        $account = AccountMother::registeredAccount();
        $account->changeStatus(false);

        $sut = $this->createSUT();

        $result = $sut->canDoThat($account, Action::Impersonate, UserMother::adminUser());

        $this->assertFalse($result);
    }

    public function testAdminCannotImpersonateRemovedAccount(): void
    {
        $account = AccountMother::registeredAccount();
        $account->remove();

        $sut = $this->createSUT();

        $result = $sut->canDoThat($account, Action::Impersonate, UserMother::adminUser());

        $this->assertFalse($result);
    }

    public function testOwnerCannotImpersonate(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat(AccountMother::registeredAccount(), Action::Impersonate, UserMother::ownerUser());

        $this->assertFalse($result);
    }

    public function testUserCannotImpersonate(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat(AccountMother::registeredAccount(), Action::Impersonate, UserMother::fromUsername('user'));

        $this->assertFalse($result);
    }

    public function testAdminCanAutocomplete(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat($this->buildSubject(), Action::Autocomplete, UserMother::adminUser());

        $this->assertTrue($result);
    }

    public function testSuperAdminCanAutocomplete(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat($this->buildSubject(), Action::Autocomplete, UserMother::superAdminUser());

        $this->assertTrue($result);
    }

    public function testOwnerCannotAutocomplete(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat($this->buildSubject(), Action::Autocomplete, UserMother::ownerUser());

        $this->assertFalse($result);
    }

    public function testUserCannotAutocomplete(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat($this->buildSubject(), Action::Autocomplete, UserMother::fromUsername('user'));

        $this->assertFalse($result);
    }

    public function testAdminCanImpersonateAccounts(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->canImpersonateAccounts(UserMother::adminUser()));
    }

    public function testSuperAdminCanImpersonateAccounts(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->canImpersonateAccounts(UserMother::superAdminUser()));
    }

    public function testOwnerCannotImpersonateAccounts(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->canImpersonateAccounts(UserMother::ownerUser()));
    }

    public function testUserCannotImpersonateAccounts(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->canImpersonateAccounts(UserMother::fromUsername('user')));
    }

    private function buildSubject(): Account
    {
        return AccountMother::registeredAccount();
    }

    private function createSUT(): AccountAuthorizer
    {
        return new AccountAuthorizer();
    }
}
