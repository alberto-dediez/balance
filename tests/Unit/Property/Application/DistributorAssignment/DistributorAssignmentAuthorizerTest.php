<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\DistributorAssignment;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DistributorAssignmentAuthorizerTest extends TestCase
{
    public function testAdminCanDoEverythingButEdit(): void
    {
        $sut = DistributorAssignmentAuthorizerMother::create();
        $assignment = DistributorAssignmentMother::forAccount(AccountId::generate(), AccountId::generate());

        foreach ([Action::Register, Action::List, Action::View, Action::Remove] as $action) {
            $this->assertTrue($sut->canDoThat($assignment, $action, UserMother::adminUser()));
            $this->assertTrue($sut->canDoThat($assignment, $action, UserMother::superAdminUser()));
        }

        $this->assertFalse($sut->canDoThat($assignment, Action::Edit, UserMother::adminUser()));
    }

    public function testOwnerOfAdheredAccountCanRemoveAssignmentByAccount(): void
    {
        $hotelId = new AccountId('hotel');
        $sut = DistributorAssignmentAuthorizerMother::create();
        $assignment = DistributorAssignmentMother::forAccount(AccountId::generate(), $hotelId);
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $hotelId);

        $this->assertTrue($sut->canDoThat($assignment, Action::Remove, $owner));
        $this->assertTrue($sut->canDoThat($assignment, Action::View, $owner));
        $this->assertFalse($sut->canDoThat($assignment, Action::Register, $owner));
    }

    public function testOwnerOfAdheredPropertyCanRemoveAssignmentByProperty(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel 1', 'hotel', (string) PropertyId::generate());
        $sut = DistributorAssignmentAuthorizerMother::create([$property]);
        $assignment = DistributorAssignmentMother::forProperty(AccountId::generate(), $property->id());

        $this->assertTrue($sut->canDoThat($assignment, Action::Remove, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('hotel'))));
    }

    public function testForeignOwnerIsDenied(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel 1', 'hotel', (string) PropertyId::generate());
        $sut = DistributorAssignmentAuthorizerMother::create([$property]);
        $foreign = UserMother::fromUsernameAndRoleAndAccount('foreign', Role::Owner, new AccountId('foreign'));

        $this->assertFalse($sut->canDoThat(DistributorAssignmentMother::forAccount(AccountId::generate(), new AccountId('hotel')), Action::Remove, $foreign));
        $this->assertFalse($sut->canDoThat(DistributorAssignmentMother::forProperty(AccountId::generate(), $property->id()), Action::Remove, $foreign));
    }

    public function testOwnerOfDistributorCannotRemove(): void
    {
        $distributorId = new AccountId('distributor');
        $sut = DistributorAssignmentAuthorizerMother::create();
        $assignment = DistributorAssignmentMother::forAccount($distributorId, AccountId::generate());

        $this->assertFalse($sut->canDoThat($assignment, Action::Remove, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $distributorId)));
    }

    public function testOwnerWithAccountCanList(): void
    {
        $sut = DistributorAssignmentAuthorizerMother::create();
        $subject = new DistributorAssignment(DistributorAssignmentId::generate());

        $this->assertTrue($sut->canDoThat($subject, Action::List, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate())));
        $this->assertFalse($sut->canDoThat($subject, Action::List, UserMother::fromUsernameAndRole('user', Role::User)));
    }

    public function testImpersonatedAccountIsUsedForRemove(): void
    {
        $hotelId = new AccountId('hotel');
        $assignment = DistributorAssignmentMother::forAccount(AccountId::generate(), $hotelId);
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('origin'));

        $this->assertTrue(DistributorAssignmentAuthorizerMother::create([], $hotelId)->canDoThat($assignment, Action::Remove, $owner));
        $this->assertFalse(DistributorAssignmentAuthorizerMother::create([], new AccountId('other'))->canDoThat($assignment, Action::Remove, $owner));
    }
}
