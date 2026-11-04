<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payee;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class PayeeAuthorizerTest extends TestCase
{
    public function testAdminCanManagePayees(): void
    {
        $payee = PayeeMother::ready();
        $user = UserMother::adminUser();

        $this->assertTrue($this->createSUT()->canDoThat($payee, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($payee, Action::Register, $user));
        $this->assertTrue($this->createSUT()->canDoThat($payee, Action::Edit, $user));
    }

    public function testOwnerCannotSeePayeesOfOtherAccounts(): void
    {
        $payee = PayeeMother::ready();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($payee, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($payee, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($payee, Action::View, $user));
    }

    public function testOwnerCanManageThePayeeOfTheirOwnAccount(): void
    {
        $accountId = AccountId::generate();
        $payee = PayeeMother::ready((string) $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($payee, Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($payee, Action::Edit, $user));
        $this->assertTrue($this->createSUT()->canDoThat($payee, Action::Register, $user));
    }

    public function testOwnerCannotEditABlockedPayeeOfTheirOwnAccount(): void
    {
        $accountId = AccountId::generate();
        $payee = PayeeMother::blocked((string) $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertFalse($this->createSUT()->canDoThat($payee, Action::Edit, $user));
        $this->assertTrue($this->createSUT()->canDoThat($payee, Action::View, $user));
    }

    public function testOwnerCannotManageThePayeeOfAProject(): void
    {
        $accountId = AccountId::generate();
        $payee = PayeeMother::ready((string) $accountId, PayeeType::Project);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertFalse($this->createSUT()->canDoThat($payee, Action::Edit, $user));
    }

    public function testOwnerCannotListNorRemovePayees(): void
    {
        $accountId = AccountId::generate();
        $payee = PayeeMother::ready((string) $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertFalse($this->createSUT()->canDoThat($payee, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($payee, Action::Remove, $user));
        $this->assertFalse($this->createSUT()->canDoThat($payee, Action::Admin, $user));
    }

    public function testOwnerCannotManageThePayeeOfTheirOwnAccountWithoutCompensationFeature(): void
    {
        $accountId = AccountId::generate();
        $payee = PayeeMother::ready((string) $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        $this->assertFalse($sut->canDoThat($payee, Action::View, $user));
        $this->assertFalse($sut->canDoThat($payee, Action::Edit, $user));
        $this->assertFalse($sut->canDoThat($payee, Action::Register, $user));
    }

    public function testAdminManagesPayeesWithCompensationFeatureDisabled(): void
    {
        $accountId = AccountId::generate();
        $payee = PayeeMother::ready((string) $accountId);
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        $this->assertTrue($sut->canDoThat($payee, Action::List, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($payee, Action::Edit, UserMother::adminUser()));
    }

    private function createSUT(): PayeeAuthorizer
    {
        return new PayeeAuthorizer();
    }

    private function createSUTWithFeatures(AccountId $accountId, AccountFeatures $features): PayeeAuthorizer
    {
        return new PayeeAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($accountId),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount($accountId, $features),
        );
    }
}
