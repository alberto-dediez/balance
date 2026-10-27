<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\ReportPermissionsPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountReportPermissions;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReportPermissionsPolicyTest extends TestCase
{
    public function testOwnerOfTheAccountGetsItsOwnPermissions(): void
    {
        $account = $this->accountWith(true, false);

        $sut = $this->createSUT([$account]);

        $this->assertPermissions(true, false, $sut->permissionsFor($account->id(), $this->userOf($account->id(), Role::Owner)));
    }

    public function testOwnerOfASupervisingConsultantGetsTheConsultantPermissions(): void
    {
        $consultant = $this->consultantWith(true, false);
        $account = $this->accountWith(false, true, $consultant->id());

        $sut = $this->createSUT([$consultant, $account]);

        $this->assertPermissions(true, false, $sut->permissionsFor($account->id(), $this->userOf($consultant->id(), Role::Owner)));
    }

    public function testOwnerOfAConsultantThatDoesNotSuperviseTheAccountGetsTheAccountPermissions(): void
    {
        $consultant = $this->consultantWith(true, false);
        $anotherConsultant = $this->consultantWith(false, false);
        $account = $this->accountWith(false, true, $anotherConsultant->id());

        $sut = $this->createSUT([$consultant, $anotherConsultant, $account]);

        $this->assertPermissions(false, true, $sut->permissionsFor($account->id(), $this->userOf($consultant->id(), Role::Owner)));
    }

    public function testInactiveConsultantDoesNotApply(): void
    {
        $consultant = $this->consultantWith(true, false);
        $account = $this->accountWith(false, true, $consultant->id());
        $consultant->changeStatus(false);

        $sut = $this->createSUT([$consultant, $account]);

        $this->assertPermissions(false, true, $sut->permissionsFor($account->id(), $this->userOf($consultant->id(), Role::Owner)));
    }

    public function testAdminWithAnAccountGetsTheAdminAccountPermissions(): void
    {
        $adminAccount = $this->accountWith(true, true);
        $account = $this->accountWith(false, false);

        $sut = $this->createSUT([$adminAccount, $account]);

        $this->assertPermissions(true, true, $sut->permissionsFor($account->id(), $this->userOf($adminAccount->id(), Role::Admin)));
    }

    public function testSuperAdminWithAnAccountGetsTheSuperAdminAccountPermissions(): void
    {
        $superAdminAccount = $this->accountWith(true, true);
        $account = $this->accountWith(false, false);

        $sut = $this->createSUT([$superAdminAccount, $account]);

        $this->assertPermissions(true, true, $sut->permissionsFor($account->id(), $this->userOf($superAdminAccount->id(), Role::SuperAdmin)));
    }

    public function testAdminWithoutAccountGetsTheAccountPermissions(): void
    {
        $account = $this->accountWith(true, false);

        $sut = $this->createSUT([$account]);

        $this->assertPermissions(true, false, $sut->permissionsFor($account->id(), UserMother::adminUser()));
    }

    public function testInactiveReportAccountKeepsItsOwnPermissions(): void
    {
        $adminAccount = $this->accountWith(true, true);
        $account = $this->accountWith(false, false);
        $account->changeStatus(false);

        $sut = $this->createSUT([$adminAccount, $account]);

        $this->assertPermissions(false, false, $sut->permissionsFor($account->id(), $this->userOf($adminAccount->id(), Role::Admin)));
    }

    public function testNullAccountIdFallsBackToDefaults(): void
    {
        $adminAccount = $this->accountWith(true, false);

        $sut = $this->createSUT([$adminAccount]);

        $this->assertPermissions(false, true, $sut->permissionsFor(null, $this->userOf($adminAccount->id(), Role::Admin)));
    }

    public function testUnknownReportAccountFallsBackToDefaults(): void
    {
        $adminAccount = $this->accountWith(true, false);

        $sut = $this->createSUT([$adminAccount]);

        $this->assertPermissions(false, true, $sut->permissionsFor(AccountId::generate(), $this->userOf($adminAccount->id(), Role::Admin)));
    }

    public function testUnknownUserAccountFallsBackToTheReportAccountPermissions(): void
    {
        $account = $this->accountWith(true, false);

        $sut = $this->createSUT([$account]);

        $this->assertPermissions(true, false, $sut->permissionsFor($account->id(), $this->userOf(AccountId::generate(), Role::Admin)));
    }

    public function testPlainUserRoleGetsTheAccountPermissions(): void
    {
        $otherAccount = $this->accountWith(true, true);
        $account = $this->accountWith(false, false);

        $sut = $this->createSUT([$otherAccount, $account]);

        $this->assertPermissions(false, false, $sut->permissionsFor($account->id(), $this->userOf($otherAccount->id(), Role::User)));
    }

    private function assertPermissions(bool $canEditTexts, bool $autoReview, AccountReportPermissions $permissions): void
    {
        $this->assertSame($canEditTexts, $permissions->canEditTexts());
        $this->assertSame($autoReview, $permissions->autoReview());
    }

    private function accountWith(bool $canEditTexts, bool $autoReview, ?AccountId $consultantAccountId = null): Account
    {
        $account = AccountMother::random();
        $account->changeReportPermissions(new AccountReportPermissions($canEditTexts, $autoReview));

        if ($consultantAccountId instanceof AccountId) {
            $account->changeConsultant($consultantAccountId);
        }

        return $account;
    }

    private function consultantWith(bool $canEditTexts, bool $autoReview): Account
    {
        $consultant = AccountMother::consultantAccount();
        $consultant->changeReportPermissions(new AccountReportPermissions($canEditTexts, $autoReview));

        return $consultant;
    }

    private function userOf(AccountId $accountId, Role $role): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('user', $role, $accountId);
    }

    /**
     * @param list<Account> $accounts
     */
    private function createSUT(array $accounts = []): ReportPermissionsPolicy
    {
        return ReportPermissionsPolicyMother::create($accounts);
    }
}
