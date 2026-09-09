<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\ImpersonationPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
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

    public function testConsultantOwnerCanImpersonateAssignedAccount(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());

        $sut = $this->createSUT($consultant, $assigned);

        $this->assertTrue($sut->canDoThat($assigned, Action::Impersonate, $this->ownerOf($consultant)));
    }

    public function testConsultantOwnerCannotImpersonateUnassignedAccount(): void
    {
        $consultant = AccountMother::consultantAccount();
        $other = AccountMother::fromName('other');

        $sut = $this->createSUT($consultant, $other);

        $this->assertFalse($sut->canDoThat($other, Action::Impersonate, $this->ownerOf($consultant)));
    }

    public function testConsultantOwnerCanImpersonateAccounts(): void
    {
        $consultant = AccountMother::consultantAccount();

        $sut = $this->createSUT($consultant);

        $this->assertTrue($sut->canImpersonateAccounts($this->ownerOf($consultant)));
    }

    public function testConsultantOwnerCanAutocomplete(): void
    {
        $consultant = AccountMother::consultantAccount();

        $sut = $this->createSUT($consultant);

        $this->assertTrue($sut->canDoThat($this->buildSubject(), Action::Autocomplete, $this->ownerOf($consultant)));
    }

    public function testConsultantOwnerCannotListAccounts(): void
    {
        $consultant = AccountMother::consultantAccount();

        $sut = $this->createSUT($consultant);

        $this->assertFalse($sut->canDoThat($this->buildSubject(), Action::List, $this->ownerOf($consultant)));
    }

    private function ownerOf(Account $account): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
    }

    private function buildSubject(): Account
    {
        return AccountMother::registeredAccount();
    }

    private function createSUT(Account ...$accounts): AccountAuthorizer
    {
        $repository = new InMemoryAccountRepository();

        foreach ($accounts as $account) {
            $repository->add($account);
        }

        return new AccountAuthorizer(impersonationPolicy: new ImpersonationPolicy($repository));
    }
}
