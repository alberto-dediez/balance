<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Dashboard;

use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\GetOwnerDashboardRequest;
use App\CarbonFootprint\Application\Dashboard\OwnerDashboardAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class OwnerDashboardAuthorizerTest extends TestCase
{
    public function testOwnerCanSeeTheDashboardOfItsAccount(): void
    {
        $request = new GetOwnerDashboardRequest(new AccountId('account-id'));
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        $this->assertTrue((new OwnerDashboardAuthorizer())->canDoThat($request, Action::List, $user));
        $this->assertTrue((new OwnerDashboardAuthorizer())->canDoThat($request, Action::View, $user));
    }

    public function testOwnerCannotSeeTheDashboardOfAnotherAccount(): void
    {
        $request = new GetOwnerDashboardRequest(new AccountId('account-id'));
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('another-account-id'));

        $this->assertFalse((new OwnerDashboardAuthorizer())->canDoThat($request, Action::List, $user));
    }

    public function testOwnerSeesTheDashboardOfTheCurrentAccount(): void
    {
        $request = new GetOwnerDashboardRequest(new AccountId('impersonated-account-id'));
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        $sut = new OwnerDashboardAuthorizer(new StubCurrentAccountIdProvider(new AccountId('impersonated-account-id')));

        $this->assertTrue($sut->canDoThat($request, Action::List, $user));
    }

    public function testUserCannotSeeTheDashboard(): void
    {
        $request = new GetOwnerDashboardRequest(new AccountId('account-id'));
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id'));

        $this->assertFalse((new OwnerDashboardAuthorizer())->canDoThat($request, Action::List, $user));
    }

    public function testNobodyCanRegisterEditNorRemoveTheDashboard(): void
    {
        $request = new GetOwnerDashboardRequest(new AccountId('account-id'));
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        foreach ([Action::Register, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse((new OwnerDashboardAuthorizer())->canDoThat($request, $action, $user));
        }
    }

    public function testOnlySupportsOwnerDashboardRequests(): void
    {
        $sut = new OwnerDashboardAuthorizer();

        $this->assertTrue($sut->supports(new GetOwnerDashboardRequest(new AccountId('account-id'))));
        $this->assertFalse($sut->supports(new \stdClass()));
    }
}
