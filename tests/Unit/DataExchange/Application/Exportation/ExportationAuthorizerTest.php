<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Exportation;

use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\DataExchange\Domain\Model\Exportation\ExportationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ExportationAuthorizerTest extends TestCase
{
    public function testAdminsReachAnyExportation(): void
    {
        $sut = ExportationAuthorizerMother::create();

        $exportation = ExportationMother::registered(accountId: AccountId::generate());

        $admin = UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, AccountId::generate());

        $this->assertTrue($sut->canDoThat($exportation, Action::List, $admin));
        $this->assertTrue($sut->canDoThat($exportation, Action::View, $admin));
        $this->assertTrue($sut->canDoThat($exportation, Action::Remove, $admin));
    }

    public function testOwnersReachTheExportationsOfTheirAccount(): void
    {
        $sut = ExportationAuthorizerMother::create();

        $accountId = AccountId::generate();

        $exportation = ExportationMother::registered(accountId: $accountId);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertTrue($sut->canDoThat($exportation, Action::View, $owner));
    }

    public function testOwnersDoNotReachTheExportationsOfAnotherAccount(): void
    {
        $sut = ExportationAuthorizerMother::create();

        $exportation = ExportationMother::registered(accountId: AccountId::generate());

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->assertFalse($sut->canDoThat($exportation, Action::View, $owner));
    }

    public function testUsersOnlyReachTheExportationsTheyRegistered(): void
    {
        $sut = ExportationAuthorizerMother::create();

        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $own = ExportationMother::registered(registeredBy: $user->id());
        $foreign = ExportationMother::registered();

        $this->assertTrue($sut->canDoThat($own, Action::View, $user));
        $this->assertFalse($sut->canDoThat($foreign, Action::View, $user));
    }

    public function testOwnersReachTheExportationsOfTheCurrentAccountWhenItIsSwitched(): void
    {
        $switchedAccountId = AccountId::generate();

        $sut = ExportationAuthorizerMother::withCurrentAccount($switchedAccountId);

        $exportation = ExportationMother::registered(accountId: $switchedAccountId);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->assertTrue($sut->canDoThat($exportation, Action::View, $owner));
    }

    public function testOwnersDoNotReachTheExportationsOfTheirOwnAccountWhileAnotherOneIsCurrent(): void
    {
        $ownAccountId = AccountId::generate();

        $sut = ExportationAuthorizerMother::withCurrentAccount(AccountId::generate());

        $exportation = ExportationMother::registered(accountId: $ownAccountId);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $ownAccountId);

        $this->assertFalse($sut->canDoThat($exportation, Action::View, $owner));
    }

    public function testTheAccountOfTheUserIsUsedWhenThereIsNoCurrentAccount(): void
    {
        $accountId = AccountId::generate();

        $sut = ExportationAuthorizerMother::withCurrentAccount(null);

        $exportation = ExportationMother::registered(accountId: $accountId);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertTrue($sut->canDoThat($exportation, Action::View, $owner));
    }

    public function testItOnlySupportsExportations(): void
    {
        $sut = ExportationAuthorizerMother::create();

        $this->assertTrue($sut->supports(ExportationMother::registered()));
        $this->assertFalse($sut->supports(UserMother::adminUser()));
    }
}
