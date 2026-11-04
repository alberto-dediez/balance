<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountFeatures;
use PHPUnit\Framework\TestCase;

class AccountFeaturesTest extends TestCase
{
    public function testAllFeaturesAreEnabledByDefault(): void
    {
        $features = new AccountFeatures();

        foreach (AccountFeature::cases() as $feature) {
            $this->assertTrue($features->isEnabled($feature));
        }
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
                $this->assertSame($feature !== $disabled, $features->isEnabled($feature));
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
}
