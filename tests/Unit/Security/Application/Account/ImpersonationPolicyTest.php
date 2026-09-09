<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\ImpersonationPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ImpersonationPolicyTest extends TestCase
{
    public function testAdminCanImpersonateAccounts(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->canImpersonateAccounts(UserMother::adminUser()));
        $this->assertTrue($sut->canImpersonateAccounts(UserMother::superAdminUser()));
    }

    public function testAdminCanImpersonateAnyActiveAccount(): void
    {
        $account = AccountMother::random();
        $sut = $this->createSUT($account);

        $this->assertTrue($sut->canImpersonate($account, UserMother::adminUser()));
    }

    public function testAdminCannotImpersonateInactiveAccount(): void
    {
        $account = AccountMother::random();
        $account->changeStatus(false);

        $sut = $this->createSUT($account);

        $this->assertFalse($sut->canImpersonate($account, UserMother::adminUser()));
    }

    public function testAdminCannotImpersonateRemovedAccount(): void
    {
        $account = AccountMother::random();
        $account->remove();

        $sut = $this->createSUT($account);

        $this->assertFalse($sut->canImpersonate($account, UserMother::adminUser()));
    }

    public function testConsultantOwnerCanImpersonateAccounts(): void
    {
        $consultant = AccountMother::consultantAccount();
        $sut = $this->createSUT($consultant);

        $this->assertTrue($sut->canImpersonateAccounts($this->ownerOf($consultant)));
    }

    public function testConsultantOwnerCanImpersonateAssignedAccount(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());

        $sut = $this->createSUT($consultant, $assigned);

        $this->assertTrue($sut->canImpersonate($assigned, $this->ownerOf($consultant)));
    }

    public function testConsultantOwnerCannotImpersonateUnassignedAccount(): void
    {
        $consultant = AccountMother::consultantAccount();
        $other = AccountMother::fromName('other');

        $sut = $this->createSUT($consultant, $other);

        $this->assertFalse($sut->canImpersonate($other, $this->ownerOf($consultant)));
    }

    public function testConsultantOwnerCannotImpersonateAccountAssignedToAnotherConsultant(): void
    {
        $consultant = AccountMother::consultantAccount();
        $anotherConsultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $anotherConsultant->id());

        $sut = $this->createSUT($consultant, $anotherConsultant, $assigned);

        $this->assertFalse($sut->canImpersonate($assigned, $this->ownerOf($consultant)));
    }

    public function testConsultantOwnerCannotImpersonateInactiveAssignedAccount(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $assigned->changeStatus(false);

        $sut = $this->createSUT($consultant, $assigned);

        $this->assertFalse($sut->canImpersonate($assigned, $this->ownerOf($consultant)));
    }

    public function testConsultantOwnerCannotImpersonateRemovedAssignedAccount(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $assigned->remove();

        $sut = $this->createSUT($consultant, $assigned);

        $this->assertFalse($sut->canImpersonate($assigned, $this->ownerOf($consultant)));
    }

    public function testOwnerOfNonConsultantAccountCannotImpersonateAccounts(): void
    {
        $account = AccountMother::fromName('regular');
        $assigned = AccountMother::fromNameWithConsultant('assigned', $account->id());

        $sut = $this->createSUT($account, $assigned);
        $owner = $this->ownerOf($account);

        $this->assertFalse($sut->canImpersonateAccounts($owner));
        $this->assertFalse($sut->canImpersonate($assigned, $owner));
    }

    public function testUserOfConsultantAccountCannotImpersonateAccounts(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());

        $sut = $this->createSUT($consultant, $assigned);
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $consultant->id());

        $this->assertFalse($sut->canImpersonateAccounts($user));
        $this->assertFalse($sut->canImpersonate($assigned, $user));
    }

    public function testOwnerOfInactiveConsultantAccountCannotImpersonateAccounts(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $consultant->changeStatus(false);

        $sut = $this->createSUT($consultant, $assigned);
        $owner = $this->ownerOf($consultant);

        $this->assertFalse($sut->canImpersonateAccounts($owner));
        $this->assertFalse($sut->canImpersonate($assigned, $owner));
    }

    public function testOwnerWithoutAccountCannotImpersonateAccounts(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->canImpersonateAccounts(UserMother::ownerUser()));
    }

    public function testConsultantOwnerCannotImpersonateWithoutRepository(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());

        $sut = new ImpersonationPolicy();
        $owner = $this->ownerOf($consultant);

        $this->assertFalse($sut->canImpersonateAccounts($owner));
        $this->assertFalse($sut->canImpersonate($assigned, $owner));
        $this->assertNull($sut->impersonableAccountsFilter($owner));
    }

    public function testImpersonableAccountsFilterForAdminHasNoConsultantRestriction(): void
    {
        $sut = $this->createSUT();

        $filter = $sut->impersonableAccountsFilter(UserMother::adminUser(), 'acme', 0);

        $this->assertInstanceOf(AccountFilter::class, $filter);
        $this->assertSame('acme', $filter->name);
        $this->assertTrue($filter->active);
        $this->assertNull($filter->consultantAccountId);
    }

    public function testImpersonableAccountsFilterForConsultantOwnerIsRestricted(): void
    {
        $consultant = AccountMother::consultantAccount();
        $sut = $this->createSUT($consultant);

        $filter = $sut->impersonableAccountsFilter($this->ownerOf($consultant));

        $this->assertInstanceOf(AccountFilter::class, $filter);
        $this->assertTrue($filter->active);
        $this->assertInstanceOf(AccountId::class, $filter->consultantAccountId);
        $this->assertTrue($consultant->id()->equals($filter->consultantAccountId));
    }

    public function testImpersonableAccountsFilterIsNullForUsersWhoCannotImpersonate(): void
    {
        $sut = $this->createSUT();

        $this->assertNull($sut->impersonableAccountsFilter(UserMother::fromUsername('user')));
    }

    private function ownerOf(Account $account): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
    }

    private function createSUT(Account ...$accounts): ImpersonationPolicy
    {
        return new ImpersonationPolicy(new InMemoryAccountRepository($this->indexed(...$accounts)));
    }

    /**
     * @return array<string, Account>
     */
    private function indexed(Account ...$accounts): array
    {
        $indexed = [];

        foreach ($accounts as $account) {
            $indexed[(string) $account->id()] = $account;
        }

        return $indexed;
    }
}
