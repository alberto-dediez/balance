<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributedPropertiesPolicyMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Application\Account\DistributorPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
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

    public function testDistributorCanViewButNotManageDistributedProperty(): void
    {
        [$sut, $distributorOwner, $property] = $this->givenADistributedProperty();

        $this->assertTrue($sut->canDoThat($property, Action::View, $distributorOwner));

        foreach ([Action::Edit, Action::Remove, Action::Export, Action::Register] as $action) {
            $this->assertFalse($sut->canDoThat($property, $action, $distributorOwner));
        }
    }

    public function testDistributorCannotViewNotDistributedProperty(): void
    {
        [$sut, $distributorOwner] = $this->givenADistributedProperty();

        $other = PropertyMother::fromNameAndAccountAndId('Other', 'other', (string) PropertyId::generate());

        $this->assertFalse($sut->canDoThat($other, Action::View, $distributorOwner));
    }

    public function testWithoutPolicyDistributedPropertyIsNotVisible(): void
    {
        [, $distributorOwner, $property] = $this->givenADistributedProperty();

        $this->assertFalse((new PropertyAuthorizer(new StubCurrentAccountIdProvider(new AccountId('distributor'))))->canDoThat($property, Action::View, $distributorOwner));
    }

    public function testActiveDistributorWithoutPropertyFeaturesCanList(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $distributor->changeFeatures($this->withoutPropertyFeatures());
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $distributor->id());
        $subject = PropertyMother::fromNameAndAccountAndId('Probe', 'distributor', (string) PropertyId::generate());

        $sut = new PropertyAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($distributor->id()),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::create([$distributor]),
            distributorPolicy: DistributorPolicyMother::create([$distributor]),
        );

        $this->assertTrue($sut->canDoThat($subject, Action::List, $owner));
        $this->assertFalse($sut->canDoThat($subject, Action::Register, $owner));
        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $owner));
        $this->assertFalse($sut->canDoThat($subject, Action::Export, $owner));
    }

    /**
     * @return array{PropertyAuthorizer, User, Property}
     */
    private function givenADistributedProperty(): array
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::fromNameAndAccountAndId('Hotel 1', 'hotel', (string) PropertyId::generate());

        /** @var list<Account> $accounts */
        $accounts = [$distributor, $hotel];

        $sut = new PropertyAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($distributor->id()),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::create($accounts),
            distributedPropertiesPolicy: DistributedPropertiesPolicyMother::create(
                $accounts,
                [$property],
                [DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id())],
            ),
            distributorPolicy: DistributorPolicyMother::create($accounts),
        );

        return [$sut, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $distributor->id()), $property];
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
