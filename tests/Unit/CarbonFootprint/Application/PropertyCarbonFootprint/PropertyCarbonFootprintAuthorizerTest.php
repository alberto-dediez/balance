<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
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

    private function createSUT(): PropertyCarbonFootprintAuthorizer
    {
        return new PropertyCarbonFootprintAuthorizer();
    }
}
