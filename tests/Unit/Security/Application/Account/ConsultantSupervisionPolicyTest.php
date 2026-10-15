<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\ConsultantSupervisionPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ConsultantSupervisionPolicyTest extends TestCase
{
    public function testConsultantSupervisesAssignedAccount(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());

        $sut = $this->createSUT([$consultant, $assigned]);

        $this->assertTrue($sut->supervises($consultant->id(), $assigned->id()));
    }

    public function testConsultantDoesNotSuperviseAccountWithoutConsultant(): void
    {
        $consultant = AccountMother::consultantAccount();
        $other = AccountMother::fromName('other');

        $sut = $this->createSUT([$consultant, $other]);

        $this->assertFalse($sut->supervises($consultant->id(), $other->id()));
    }

    public function testConsultantDoesNotSuperviseAccountAssignedToAnotherConsultant(): void
    {
        $consultant = AccountMother::consultantAccount();
        $anotherConsultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $anotherConsultant->id());

        $sut = $this->createSUT([$consultant, $anotherConsultant, $assigned]);

        $this->assertFalse($sut->supervises($consultant->id(), $assigned->id()));
    }

    public function testConsultantDoesNotSuperviseRemovedAccount(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $assigned->remove();

        $sut = $this->createSUT([$consultant, $assigned]);

        $this->assertFalse($sut->supervises($consultant->id(), $assigned->id()));
    }

    public function testInactiveConsultantDoesNotSupervise(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $consultant->changeStatus(false);

        $sut = $this->createSUT([$consultant, $assigned]);

        $this->assertFalse($sut->isActiveConsultant($consultant->id()));
        $this->assertFalse($sut->supervises($consultant->id(), $assigned->id()));
    }

    public function testRemovedConsultantDoesNotSupervise(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $consultant->remove();

        $sut = $this->createSUT([$consultant, $assigned]);

        $this->assertFalse($sut->supervises($consultant->id(), $assigned->id()));
    }

    public function testUnmarkedConsultantDoesNotSupervise(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $consultant->changeConsultantStatus(false);

        $sut = $this->createSUT([$consultant, $assigned]);

        $this->assertFalse($sut->supervises($consultant->id(), $assigned->id()));
    }

    public function testNothingIsSupervisedWithoutRepository(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());

        $sut = new ConsultantSupervisionPolicy();

        $this->assertFalse($sut->isActiveConsultant($consultant->id()));
        $this->assertFalse($sut->supervises($consultant->id(), $assigned->id()));
        $this->assertSame([], $sut->supervisedAccountIds($consultant->id()));
        $this->assertSame([], $sut->supervisorsOf($assigned->id()));
    }

    public function testSupervisedAccountIdsReturnsAssignedAccounts(): void
    {
        $consultant = AccountMother::consultantAccount();
        $anotherConsultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $inactive = AccountMother::fromNameWithConsultant('inactive', $consultant->id());
        $inactive->changeStatus(false);
        $removed = AccountMother::fromNameWithConsultant('removed', $consultant->id());
        $removed->remove();
        $foreign = AccountMother::fromNameWithConsultant('foreign', $anotherConsultant->id());

        $sut = $this->createSUT([$consultant, $anotherConsultant, $assigned, $inactive, $removed, $foreign]);

        $this->assertSame(
            [(string) $assigned->id(), (string) $inactive->id()],
            array_map(strval(...), $sut->supervisedAccountIds($consultant->id())),
        );
    }

    public function testSupervisedAccountIdsIsEmptyForInvalidConsultant(): void
    {
        $regular = AccountMother::fromName('regular');
        $assigned = AccountMother::fromNameWithConsultant('assigned', $regular->id());

        $sut = $this->createSUT([$regular, $assigned]);

        $this->assertSame([], $sut->supervisedAccountIds($regular->id()));
    }

    public function testSupervisorsOfReturnsOnlyActiveOwnersOfTheConsultant(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $owner = $this->userOf($consultant->id(), 'owner', Role::Owner);
        $employee = $this->userOf($consultant->id(), 'employee', Role::User);
        $removedOwner = $this->userOf($consultant->id(), 'removed', Role::Owner);
        $removedOwner->remove();
        $assignedOwner = $this->userOf($assigned->id(), 'client', Role::Owner);

        $sut = $this->createSUT([$consultant, $assigned], [$owner, $employee, $removedOwner, $assignedOwner]);

        $this->assertSame([$owner], $sut->supervisorsOf($assigned->id()));
    }

    public function testSupervisorsOfIsEmptyWithoutConsultant(): void
    {
        $account = AccountMother::fromName('regular');
        $owner = $this->userOf($account->id(), 'owner', Role::Owner);

        $sut = $this->createSUT([$account], [$owner]);

        $this->assertSame([], $sut->supervisorsOf($account->id()));
    }

    public function testSupervisorsOfIsEmptyWithInactiveConsultant(): void
    {
        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('assigned', $consultant->id());
        $consultant->changeStatus(false);
        $owner = $this->userOf($consultant->id(), 'owner', Role::Owner);

        $sut = $this->createSUT([$consultant, $assigned], [$owner]);

        $this->assertSame([], $sut->supervisorsOf($assigned->id()));
    }

    public function testSupervisorsOfIsEmptyWithoutAccount(): void
    {
        $sut = $this->createSUT();

        $this->assertSame([], $sut->supervisorsOf(null));
    }

    private function userOf(AccountId $accountId, string $username, Role $role): User
    {
        return UserMother::fromUsernameAndRoleAndAccount($username, $role, $accountId);
    }

    /**
     * @param list<Account> $accounts
     * @param list<User>    $users
     */
    private function createSUT(array $accounts = [], array $users = []): ConsultantSupervisionPolicy
    {
        return ConsultantSupervisionPolicyMother::create($accounts, $users);
    }
}
