<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class OrganizationalEmployeeContributionAuthorizerTest extends TestCase
{
    private OrganizationalEmployeeContributionAuthorizer $sut;

    protected function setUp(): void
    {
        $this->sut = new OrganizationalEmployeeContributionAuthorizer();
    }

    public function testOwnerOfTheAccountCanViewEditAndRemove(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1', 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        foreach ([Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertTrue($this->sut->canDoThat($contribution, $action, $owner), $action->value);
        }
    }

    public function testOwnerOfAnotherAccountCannot(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1', 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account'));

        $this->assertFalse($this->sut->canDoThat($contribution, Action::View, $owner));
        $this->assertFalse($this->sut->canDoThat($contribution, Action::Edit, $owner));
    }

    public function testAdminAndUserCannot(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1', 'account-id');

        foreach ([Role::Admin, Role::User] as $role) {
            $user = UserMother::fromUsernameAndRoleAndAccount('someone', $role, new AccountId('account-id'));

            $this->assertFalse($this->sut->canDoThat($contribution, Action::View, $user));
            $this->assertFalse($this->sut->canDoThat($contribution, Action::Edit, $user));
        }
    }
}
