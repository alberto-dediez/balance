<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\DistributorAssignment;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class DistributedPropertiesPolicyTest extends TestCase
{
    public function testDistributesPropertyOfAssignedAccount(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());

        $sut = DistributedPropertiesPolicyMother::create(
            [$distributor, $hotel],
            [$property],
            [DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id())],
        );

        $this->assertTrue($sut->distributes($distributor->id(), $property));
    }

    public function testDistributesAssignedProperty(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $assigned = PropertyMother::from(PropertyId::generate(), 'Assigned', $hotel->id());
        $notAssigned = PropertyMother::from(PropertyId::generate(), 'Not assigned', $hotel->id());

        $sut = DistributedPropertiesPolicyMother::create(
            [$distributor, $hotel],
            [$assigned, $notAssigned],
            [DistributorAssignmentMother::forProperty($distributor->id(), $assigned->id())],
        );

        $this->assertTrue($sut->distributes($distributor->id(), $assigned));
        $this->assertFalse($sut->distributes($distributor->id(), $notAssigned));
    }

    public function testDoesNotDistributeNotAssignedProperty(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());

        $sut = DistributedPropertiesPolicyMother::create([$distributor, $hotel], [$property]);

        $this->assertFalse($sut->distributes($distributor->id(), $property));
    }

    public function testUnmarkedOrInactiveDistributorDoesNotDistribute(): void
    {
        $unmarked = AccountMother::distributorAccountFromNameAndId('Unmarked', 'unmarked');
        $unmarked->changeDistributorStatus(false);
        $inactive = AccountMother::distributorAccountFromNameAndId('Inactive', 'inactive');
        $inactive->changeStatus(false);
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());

        $sut = DistributedPropertiesPolicyMother::create(
            [$unmarked, $inactive, $hotel],
            [$property],
            [
                DistributorAssignmentMother::forAccount($unmarked->id(), $hotel->id()),
                DistributorAssignmentMother::forAccount($inactive->id(), $hotel->id()),
            ],
        );

        $this->assertFalse($sut->distributes($unmarked->id(), $property));
        $this->assertFalse($sut->distributes($inactive->id(), $property));
        $this->assertTrue($sut->scopeFor($unmarked->id())->isEmpty());
    }

    public function testHotelWithoutPropertyTripCalculatorsIsNotDistributed(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::withFeatures('Hotel', 'hotel', new AccountFeatures(propertyTripCalculators: false));
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());

        $sut = DistributedPropertiesPolicyMother::create(
            [$distributor, $hotel],
            [$property],
            [DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id())],
        );

        $this->assertFalse($sut->distributes($distributor->id(), $property));
    }

    public function testScopeForWithoutRequiredFeature(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::withFeatures('Hotel', 'hotel', new AccountFeatures(propertyTripCalculators: false));
        $other = AccountMother::withFeatures('Other', 'other', new AccountFeatures(propertyTripCalculators: false));
        $property = PropertyMother::from(PropertyId::generate(), 'Other 1', $other->id());

        $sut = DistributedPropertiesPolicyMother::create(
            [$distributor, $hotel, $other],
            [$property],
            [
                DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id()),
                DistributorAssignmentMother::forProperty($distributor->id(), $property->id()),
                DistributorAssignmentMother::forAccount(AccountId::generate(), $other->id()),
            ],
        );

        $scope = $sut->scopeFor($distributor->id());

        $this->assertSame([(string) $hotel->id()], array_map('strval', $scope->accountIds));
        $this->assertSame([(string) $property->id()], array_map('strval', $scope->propertyIds));
    }

    public function testScopeForWithRequiredFeatureDiscardsAccountsAndPropertiesWithoutIt(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $enabled = AccountMother::fromNameAndId('Enabled', 'enabled');
        $disabled = AccountMother::withFeatures('Disabled', 'disabled', new AccountFeatures(propertyTripCalculators: false));
        $enabledProperty = PropertyMother::from(PropertyId::generate(), 'Enabled 1', $enabled->id());
        $disabledProperty = PropertyMother::from(PropertyId::generate(), 'Disabled 1', $disabled->id());

        $sut = DistributedPropertiesPolicyMother::create(
            [$distributor, $enabled, $disabled],
            [$enabledProperty, $disabledProperty],
            [
                DistributorAssignmentMother::forAccount($distributor->id(), $enabled->id()),
                DistributorAssignmentMother::forAccount($distributor->id(), $disabled->id()),
                DistributorAssignmentMother::forProperty($distributor->id(), $enabledProperty->id()),
                DistributorAssignmentMother::forProperty($distributor->id(), $disabledProperty->id()),
            ],
        );

        $scope = $sut->scopeFor($distributor->id(), AccountFeature::PropertyTripCalculators);

        $this->assertSame([(string) $enabled->id()], array_map('strval', $scope->accountIds));
        $this->assertSame([(string) $enabledProperty->id()], array_map('strval', $scope->propertyIds));
    }

    public function testScopeForHasNoDuplicates(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());

        $sut = DistributedPropertiesPolicyMother::create(
            [$distributor, $hotel],
            [$property],
            [
                DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id()),
                DistributorAssignmentMother::forProperty($distributor->id(), $property->id()),
                DistributorAssignmentMother::forProperty($distributor->id(), $property->id()),
            ],
        );

        $scope = $sut->scopeFor($distributor->id());

        $this->assertCount(1, $scope->accountIds);
        $this->assertCount(1, $scope->propertyIds);
    }
}
