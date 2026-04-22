<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Company;

use App\Property\Application\Company\CompanyAuthorizer;
use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CompanyAuthorizerTest extends TestCase
{
    public function testAdminCanListCompanies(): void
    {
        $user = UserMother::adminUser();
        $subject = new Company(CompanyId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterCompanies(): void
    {
        $user = UserMother::adminUser();
        $subject = new Company(CompanyId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditCompany(): void
    {
        $user = UserMother::adminUser();
        $subject = CompanyMother::from(name: 'Test', id: (string) CompanyId::generate(), accountId: 'some-account-id');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveCompany(): void
    {
        $user = UserMother::adminUser();
        $subject = CompanyMother::from(name: 'Test', id: (string) CompanyId::generate(), accountId: 'some-account-id');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCanListCompaniesOfTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new Company(id: CompanyId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterCompanyForTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new Company(id: CompanyId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditCompanyOfTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CompanyMother::from(name: 'Test', id: (string) CompanyId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditCompanyOfOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CompanyMother::from(name: 'Test', id: (string) CompanyId::generate(), accountId: 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveCompanyOfTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CompanyMother::from(name: 'Test', id: (string) CompanyId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Company(CompanyId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListCompanies(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Company(CompanyId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): CompanyAuthorizer
    {
        return new CompanyAuthorizer();
    }
}
