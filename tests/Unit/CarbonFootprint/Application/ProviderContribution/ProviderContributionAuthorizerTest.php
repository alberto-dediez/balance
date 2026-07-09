<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution;

use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ProviderContributionAuthorizerTest extends TestCase
{
    public function testOwnerCanViewProviderContributionOfTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testOwnerCanEditProviderContributionOfTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditProviderContributionOfOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testUserWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotEdit(): void
    {
        $user = UserMother::adminUser();
        $subject = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    private function createSUT(): ProviderContributionAuthorizer
    {
        return new ProviderContributionAuthorizer();
    }
}
