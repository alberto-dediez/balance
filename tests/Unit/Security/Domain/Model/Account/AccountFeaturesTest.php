<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountFeatures;
use PHPUnit\Framework\TestCase;

class AccountFeaturesTest extends TestCase
{
    public function testAllFeaturesButContestsAreEnabledByDefault(): void
    {
        $features = new AccountFeatures();

        foreach (AccountFeature::cases() as $feature) {
            $this->assertSame(AccountFeature::Contests !== $feature, $features->isEnabled($feature));
        }
    }

    public function testContestsIsDisabledByDefault(): void
    {
        $features = new AccountFeatures();

        $this->assertFalse($features->contests());
        $this->assertFalse($features->isEnabled(AccountFeature::Contests));
    }

    public function testContestsCanBeEnabled(): void
    {
        $this->assertTrue((new AccountFeatures(contests: true))->isEnabled(AccountFeature::Contests));
    }

    public function testIsEnabledReflectsEachDisabledFeature(): void
    {
        $cases = [
            [AccountFeature::AccountTripCalculator, new AccountFeatures(accountTripCalculator: false)],
            [AccountFeature::PropertyTripCalculators, new AccountFeatures(propertyTripCalculators: false)],
            [AccountFeature::Events, new AccountFeatures(events: false)],
            [AccountFeature::Compensation, new AccountFeatures(compensation: false)],
            [AccountFeature::OrganizationalCarbonFootprint, new AccountFeatures(organizationalCarbonFootprint: false)],
        ];

        foreach ($cases as [$disabled, $features]) {
            foreach (AccountFeature::cases() as $feature) {
                $this->assertSame($feature !== $disabled && AccountFeature::Contests !== $feature, $features->isEnabled($feature));
            }
        }
    }

    public function testEqualsWithSameValues(): void
    {
        $this->assertTrue((new AccountFeatures(events: false))->equals(new AccountFeatures(events: false)));
    }

    public function testNotEqualsWithDifferentValues(): void
    {
        $this->assertFalse((new AccountFeatures())->equals(new AccountFeatures(compensation: false)));
    }

    public function testNotEqualsWithDifferentContests(): void
    {
        $this->assertFalse((new AccountFeatures())->equals(new AccountFeatures(contests: true)));
    }
}
