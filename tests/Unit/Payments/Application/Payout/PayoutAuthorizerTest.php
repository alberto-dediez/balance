<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class PayoutAuthorizerTest extends TestCase
{
    public function testAdminCanManagePayouts(): void
    {
        $payout = PayoutMother::draft();
        $user = UserMother::adminUser();

        $authorizer = $this->createSUT();

        $this->assertTrue($authorizer->canDoThat($payout, Action::List, $user));
        $this->assertTrue($authorizer->canDoThat($payout, Action::View, $user));
        $this->assertTrue($authorizer->canDoThat($payout, Action::Register, $user));
        $this->assertTrue($authorizer->canDoThat($payout, Action::Edit, $user));
        $this->assertTrue($authorizer->canDoThat($payout, Action::Remove, $user));
    }

    public function testSuperAdminCanManagePayouts(): void
    {
        $payout = PayoutMother::draft();
        $user = UserMother::from(UserId::generate(), role: Role::SuperAdmin);

        $authorizer = $this->createSUT();

        $this->assertTrue($authorizer->canDoThat($payout, Action::List, $user));
        $this->assertTrue($authorizer->canDoThat($payout, Action::View, $user));
        $this->assertTrue($authorizer->canDoThat($payout, Action::Register, $user));
        $this->assertTrue($authorizer->canDoThat($payout, Action::Edit, $user));
        $this->assertTrue($authorizer->canDoThat($payout, Action::Remove, $user));
    }

    public function testOwnerCanOnlyListAndViewPayouts(): void
    {
        $payout = PayoutMother::draft();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $authorizer = $this->createSUT();

        $this->assertTrue($authorizer->canDoThat($payout, Action::List, $user));
        $this->assertTrue($authorizer->canDoThat($payout, Action::View, $user));
        $this->assertFalse($authorizer->canDoThat($payout, Action::Register, $user));
        $this->assertFalse($authorizer->canDoThat($payout, Action::Edit, $user));
        $this->assertFalse($authorizer->canDoThat($payout, Action::Remove, $user));
    }

    public function testNormalUserCannotManagePayouts(): void
    {
        $payout = PayoutMother::draft();
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $authorizer = $this->createSUT();

        $this->assertFalse($authorizer->canDoThat($payout, Action::List, $user));
        $this->assertFalse($authorizer->canDoThat($payout, Action::View, $user));
        $this->assertFalse($authorizer->canDoThat($payout, Action::Register, $user));
        $this->assertFalse($authorizer->canDoThat($payout, Action::Edit, $user));
        $this->assertFalse($authorizer->canDoThat($payout, Action::Remove, $user));
    }

    public function testOwnerCannotListNorViewPayoutsWithoutCompensationFeature(): void
    {
        $accountId = AccountId::generate();
        $payout = PayoutMother::draft();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        $this->assertFalse($sut->canDoThat($payout, Action::List, $user));
        $this->assertFalse($sut->canDoThat($payout, Action::View, $user));
    }

    public function testAdminManagesPayoutsWithCompensationFeatureDisabled(): void
    {
        $accountId = AccountId::generate();
        $payout = PayoutMother::draft();
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        $this->assertTrue($sut->canDoThat($payout, Action::List, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($payout, Action::Edit, UserMother::adminUser()));
    }

    private function createSUT(): PayoutAuthorizer
    {
        return new PayoutAuthorizer();
    }

    private function createSUTWithFeatures(AccountId $accountId, AccountFeatures $features): PayoutAuthorizer
    {
        return new PayoutAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($accountId),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount($accountId, $features),
        );
    }
}
