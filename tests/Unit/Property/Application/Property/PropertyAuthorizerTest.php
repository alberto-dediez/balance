<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class PropertyAuthorizerTest extends TestCase
{
    public function testOwnerCanManagePropertiesOfTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id-test', (string) PropertyId::generate());

        $this->assertTrue((new PropertyAuthorizer())->canDoThat($subject, Action::List, $user));
        $this->assertTrue((new PropertyAuthorizer())->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanManageProperties(): void
    {
        $subject = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id-test', (string) PropertyId::generate());

        $this->assertTrue((new PropertyAuthorizer())->canDoThat($subject, Action::Edit, UserMother::adminUser()));
    }

    public function testOwnerCanManageWithOnlyEventsFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id-test', (string) PropertyId::generate());
        $sut = $this->createSUTWithFeatures(new AccountFeatures(propertyTripCalculators: false, organizationalCarbonFootprint: false));

        $this->assertTrue($sut->canDoThat($subject, Action::List, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanManageWithOnlyOrganizationalCarbonFootprintFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id-test', (string) PropertyId::generate());
        $sut = $this->createSUTWithFeatures(new AccountFeatures(propertyTripCalculators: false, events: false));

        $this->assertTrue($sut->canDoThat($subject, Action::List, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanManageWithOnlyPropertyTripCalculatorsFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id-test', (string) PropertyId::generate());
        $sut = $this->createSUTWithFeatures(new AccountFeatures(events: false, organizationalCarbonFootprint: false));

        $this->assertTrue($sut->canDoThat($subject, Action::List, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotManageWithoutEventsNorOrganizationalCarbonFootprintNorPropertyTripCalculators(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id-test', (string) PropertyId::generate());
        $sut = $this->createSUTWithFeatures($this->withoutPropertyFeatures());

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse($sut->canDoThat($subject, $action, $user));
        }
    }

    public function testAdminCanManageWithoutEventsNorOrganizationalCarbonFootprintNorPropertyTripCalculators(): void
    {
        $subject = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id-test', (string) PropertyId::generate());
        $sut = $this->createSUTWithFeatures($this->withoutPropertyFeatures());

        $this->assertTrue($sut->canDoThat($subject, Action::List, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, UserMother::adminUser()));
    }

    private function withoutPropertyFeatures(): AccountFeatures
    {
        return new AccountFeatures(propertyTripCalculators: false, events: false, organizationalCarbonFootprint: false);
    }

    private function createSUTWithFeatures(AccountFeatures $features): PropertyAuthorizer
    {
        return new PropertyAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId('account-id-test')),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId('account-id-test'), $features),
        );
    }
}
