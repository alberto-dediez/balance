<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class OrganizationalProviderContributionAuthorizerTest extends TestCase
{
    private OrganizationalProviderContributionAuthorizer $sut;

    protected function setUp(): void
    {
        $this->sut = new OrganizationalProviderContributionAuthorizer();
    }

    public function testOwnerOfTheAccountCanDoEverything(): void
    {
        $contribution = OrganizationalProviderContributionMother::manual(accountId: 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertTrue($this->sut->canDoThat($contribution, $action, $owner), $action->value);
        }
    }

    public function testOwnerOfAnotherAccountCannot(): void
    {
        $contribution = OrganizationalProviderContributionMother::manual(accountId: 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account'));

        $this->assertFalse($this->sut->canDoThat($contribution, Action::View, $owner));
        $this->assertFalse($this->sut->canDoThat($contribution, Action::Edit, $owner));
    }

    public function testAdminAndUserCannot(): void
    {
        $contribution = OrganizationalProviderContributionMother::manual(accountId: 'account-id');

        foreach ([Role::Admin, Role::User] as $role) {
            $user = UserMother::fromUsernameAndRoleAndAccount('someone', $role, new AccountId('account-id'));

            $this->assertFalse($this->sut->canDoThat($contribution, Action::View, $user));
            $this->assertFalse($this->sut->canDoThat($contribution, Action::Remove, $user));
        }
    }
}
