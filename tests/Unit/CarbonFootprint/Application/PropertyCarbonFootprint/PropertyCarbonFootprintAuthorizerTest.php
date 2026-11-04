<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class PropertyCarbonFootprintAuthorizerTest extends TestCase
{
    public function testOwnerCanList(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegister(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            propertyId: PropertyId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotEditFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotList(): void
    {
        $user = UserMother::adminUser();
        $subject = new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRemoveSoThatRemovingThePropertyCascadesToItsFootprints(): void
    {
        $user = UserMother::adminUser();
        $subject = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanExportFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Export, $user));
    }

    public function testOwnerCannotExportFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Export, $user));
    }

    public function testUserCannotExport(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Export, $user));
    }

    public function testOwnerCannotListNorEditWithoutOrganizationalCarbonFootprintFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');
        $sut = $this->createSUTWithFeatures('account-id-test', new AccountFeatures(organizationalCarbonFootprint: false));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Export] as $action) {
            $this->assertFalse($sut->canDoThat($subject, $action, $user));
        }
    }

    public function testAdminCanRemoveWithOrganizationalCarbonFootprintFeatureDisabled(): void
    {
        $subject = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');
        $sut = $this->createSUTWithFeatures('account-id-test', new AccountFeatures(organizationalCarbonFootprint: false));

        $this->assertTrue($sut->canDoThat($subject, Action::Remove, UserMother::adminUser()));
    }

    private function createSUT(): PropertyCarbonFootprintAuthorizer
    {
        return new PropertyCarbonFootprintAuthorizer();
    }

    private function createSUTWithFeatures(string $accountId, AccountFeatures $features): PropertyCarbonFootprintAuthorizer
    {
        return new PropertyCarbonFootprintAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId($accountId)),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId($accountId), $features),
        );
    }
}
