<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class DistributorPolicyTest extends TestCase
{
    public function testMarkedAndActiveAccountIsActiveDistributor(): void
    {
        $distributor = AccountMother::distributorAccount();

        $sut = DistributorPolicyMother::create([$distributor]);

        $this->assertTrue($sut->isActiveDistributor($distributor->id()));
    }

    public function testUnmarkedAccountIsNotDistributor(): void
    {
        $account = AccountMother::fromName('account');

        $sut = DistributorPolicyMother::create([$account]);

        $this->assertFalse($sut->isActiveDistributor($account->id()));
    }

    public function testInactiveDistributorIsNotActiveDistributor(): void
    {
        $distributor = AccountMother::distributorAccount();
        $distributor->changeStatus(false);

        $sut = DistributorPolicyMother::create([$distributor]);

        $this->assertFalse($sut->isActiveDistributor($distributor->id()));
    }

    public function testRemovedDistributorIsNotActiveDistributor(): void
    {
        $distributor = AccountMother::distributorAccount();
        $distributor->remove();

        $sut = DistributorPolicyMother::create([$distributor]);

        $this->assertFalse($sut->isActiveDistributor($distributor->id()));
    }

    public function testUnknownAccountIsNotDistributor(): void
    {
        $sut = DistributorPolicyMother::create();

        $this->assertFalse($sut->isActiveDistributor(AccountId::generate()));
    }
}
