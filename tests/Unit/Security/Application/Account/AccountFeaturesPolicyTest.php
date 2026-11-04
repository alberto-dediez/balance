<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class AccountFeaturesPolicyTest extends TestCase
{
    public function testEnabledFeatureIsEnabled(): void
    {
        $account = AccountMother::fromName('MyAccount');

        $sut = AccountFeaturesPolicyMother::create([$account]);

        $this->assertTrue($sut->isEnabled(AccountFeature::Events, $account->id()));
    }

    public function testDisabledFeatureIsNotEnabled(): void
    {
        $account = AccountMother::withFeatures('MyAccount', (string) AccountId::generate(), new AccountFeatures(events: false));

        $sut = AccountFeaturesPolicyMother::create([$account]);

        $this->assertFalse($sut->isEnabled(AccountFeature::Events, $account->id()));
        $this->assertTrue($sut->isEnabled(AccountFeature::Compensation, $account->id()));
    }

    public function testWithoutAccountNoFeatureIsEnabled(): void
    {
        $sut = AccountFeaturesPolicyMother::create();

        $this->assertFalse($sut->isEnabled(AccountFeature::Events, null));
    }

    public function testUnknownAccountHasNoFeatureEnabled(): void
    {
        $sut = AccountFeaturesPolicyMother::create([AccountMother::fromName('MyAccount')]);

        $this->assertFalse($sut->isEnabled(AccountFeature::Events, AccountId::generate()));
    }

    public function testTheAccountIsLoadedOnceForSeveralChecks(): void
    {
        $account = AccountMother::fromName('MyAccount');

        $repository = $this->createMock(AccountRepository::class);
        $repository->expects($this->once())->method('find')->with($account->id())->willReturn($account);

        $sut = new AccountFeaturesPolicy($repository);

        $this->assertTrue($sut->isEnabled(AccountFeature::Events, $account->id()));
        $this->assertTrue($sut->isEnabled(AccountFeature::Compensation, $account->id()));
    }

    public function testAnUnknownAccountIsLoadedOnceForSeveralChecks(): void
    {
        $repository = $this->createMock(AccountRepository::class);
        $repository->expects($this->once())->method('find')->willReturn(null);

        $sut = new AccountFeaturesPolicy($repository);
        $accountId = AccountId::generate();

        $this->assertFalse($sut->isEnabled(AccountFeature::Events, $accountId));
        $this->assertFalse($sut->isEnabled(AccountFeature::Compensation, $accountId));
    }

    public function testTheAccountIsLoadedAgainAfterReset(): void
    {
        $account = AccountMother::fromName('MyAccount');

        $repository = $this->createMock(AccountRepository::class);
        $repository->expects($this->exactly(2))->method('find')->with($account->id())->willReturn($account);

        $sut = new AccountFeaturesPolicy($repository);

        $sut->isEnabled(AccountFeature::Events, $account->id());
        $sut->reset();
        $sut->isEnabled(AccountFeature::Events, $account->id());
    }
}
