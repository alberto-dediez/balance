<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution;

use App\CarbonFootprint\Application\EmployeeContribution\EmployeeContributionAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class EmployeeContributionAuthorizerTest extends TestCase
{
    public function testOwnerCanListAndEditEmployeeContributionsOfTheirAccount(): void
    {
        $subject = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id-test');
        $sut = $this->createSUT(new AccountFeatures());

        $this->assertTrue($sut->canDoThat($subject, Action::List, $this->owner()));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $this->owner()));
    }

    public function testAdminCannotEditEmployeeContributions(): void
    {
        $subject = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id-test');

        $this->assertFalse((new EmployeeContributionAuthorizer())->canDoThat($subject, Action::Edit, UserMother::adminUser()));
    }

    public function testOwnerCannotListNorEditEmployeeContributionsWithoutEventsFeature(): void
    {
        $subject = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id-test');
        $sut = $this->createSUT(new AccountFeatures(events: false));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse($sut->canDoThat($subject, $action, $this->owner()));
        }
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }

    private function createSUT(AccountFeatures $features): EmployeeContributionAuthorizer
    {
        return new EmployeeContributionAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId('account-id-test')),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId('account-id-test'), $features),
        );
    }
}
