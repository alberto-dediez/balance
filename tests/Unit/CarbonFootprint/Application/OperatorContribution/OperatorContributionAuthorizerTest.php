<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class OperatorContributionAuthorizerTest extends TestCase
{
    public function testOwnerOfTheAccountCanDoEverything(): void
    {
        $sut = new OperatorContributionAuthorizer();
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
        $contribution = OperatorContributionMother::pending(accountId: 'account-id');
        $footprint = CompanyCarbonFootprintMother::fromCompanyIdAndAccount('company-id', 'account-id');

        $this->assertTrue($sut->canDoThat($footprint, Action::List, $owner));
        $this->assertTrue($sut->canDoThat($footprint, Action::Register, $owner));

        foreach ([Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertTrue($sut->canDoThat($contribution, $action, $owner), $action->value);
        }
    }

    public function testOwnerOfAnotherAccountCannot(): void
    {
        $sut = new OperatorContributionAuthorizer();
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account'));

        $this->assertFalse($sut->canDoThat(CompanyCarbonFootprintMother::fromCompanyIdAndAccount('company-id', 'account-id'), Action::Register, $owner));
        $this->assertFalse($sut->canDoThat(OperatorContributionMother::pending(accountId: 'account-id'), Action::View, $owner));
        $this->assertFalse($sut->canDoThat(OperatorContributionMother::pending(accountId: 'account-id'), Action::Edit, $owner));
    }

    public function testAdminAndUserCannot(): void
    {
        $sut = new OperatorContributionAuthorizer();
        $contribution = OperatorContributionMother::pending(accountId: 'account-id');
        $footprint = CompanyCarbonFootprintMother::fromCompanyIdAndAccount('company-id', 'account-id');

        foreach ([Role::Admin, Role::User] as $role) {
            $user = UserMother::fromUsernameAndRoleAndAccount('someone', $role, new AccountId('account-id'));

            $this->assertFalse($sut->canDoThat($footprint, Action::Register, $user));
            $this->assertFalse($sut->canDoThat($contribution, Action::View, $user));
            $this->assertFalse($sut->canDoThat($contribution, Action::Remove, $user));
        }
    }

    public function testImpersonatingOwnerActsOnTheCurrentAccount(): void
    {
        $sut = new OperatorContributionAuthorizer(new StubCurrentAccountIdProvider(new AccountId('account-id')));
        $consultant = UserMother::fromUsernameAndRoleAndAccount('consultant', Role::Owner, new AccountId('consultant-account'));

        $this->assertTrue($sut->canDoThat(CompanyCarbonFootprintMother::fromCompanyIdAndAccount('company-id', 'account-id'), Action::Register, $consultant));
        $this->assertTrue($sut->canDoThat(OperatorContributionMother::pending(accountId: 'account-id'), Action::Edit, $consultant));
    }

    public function testOwnerCannotDoAnythingWithoutOrganizationalCarbonFootprintFeature(): void
    {
        $sut = $this->createSUTWithFeatures('account-id', new AccountFeatures(organizationalCarbonFootprint: false));
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
        $contribution = OperatorContributionMother::pending(accountId: 'account-id');
        $footprint = CompanyCarbonFootprintMother::fromCompanyIdAndAccount('company-id', 'account-id');

        $this->assertFalse($sut->canDoThat($footprint, Action::List, $owner));
        $this->assertFalse($sut->canDoThat($footprint, Action::Register, $owner));

        foreach ([Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse($sut->canDoThat($contribution, $action, $owner), $action->value);
        }
    }

    private function createSUTWithFeatures(string $accountId, AccountFeatures $features): OperatorContributionAuthorizer
    {
        return new OperatorContributionAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId($accountId)),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId($accountId), $features),
        );
    }
}
