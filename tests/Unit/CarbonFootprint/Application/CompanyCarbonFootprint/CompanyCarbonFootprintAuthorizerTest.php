<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CompanyCarbonFootprintAuthorizerTest extends TestCase
{
    public function testOwnerCanList(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new CompanyCarbonFootprint(
            id: CompanyCarbonFootprintId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegister(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new CompanyCarbonFootprint(
            id: CompanyCarbonFootprintId::generate(),
            companyId: CompanyId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CompanyCarbonFootprintMother::fromCompanyIdAndAccount((string) CompanyId::generate(), 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotEditFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CompanyCarbonFootprintMother::fromCompanyIdAndAccount((string) CompanyId::generate(), 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new CompanyCarbonFootprint(
            id: CompanyCarbonFootprintId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotList(): void
    {
        $user = UserMother::adminUser();
        $subject = new CompanyCarbonFootprint(
            id: CompanyCarbonFootprintId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRemoveSoThatRemovingTheCompanyCascadesToItsFootprints(): void
    {
        $user = UserMother::adminUser();
        $subject = CompanyCarbonFootprintMother::fromCompanyIdAndAccount((string) CompanyId::generate(), 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    private function createSUT(): CompanyCarbonFootprintAuthorizer
    {
        return new CompanyCarbonFootprintAuthorizer();
    }
}
