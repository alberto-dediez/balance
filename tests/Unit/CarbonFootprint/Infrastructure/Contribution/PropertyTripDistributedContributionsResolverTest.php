<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Contribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class PropertyTripDistributedContributionsResolverTest extends TestCase
{
    public function testScopeIncludesPropertyContributionsOfAssignedAccounts(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');

        $scope = PropertyTripDistributedContributionsResolverMother::create(
            [$distributor, $hotel],
            [],
            [DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id())],
        )->scopeFor($distributor->id());

        $this->assertTrue($scope->includes(ProjectContributionMother::pending($hotel->id())));
        $this->assertTrue($scope->includes(ProjectContributionMother::pending($hotel->id(), origin: ProjectContributionOrigin::Property)));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending($hotel->id(), origin: ProjectContributionOrigin::EventCarbonFootprint)));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending($hotel->id(), origin: ProjectContributionOrigin::Account)));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending()));
    }

    public function testScopeIncludesContributionsOfAssignedPropertiesOnly(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $assigned = PropertyMother::from(PropertyId::generate(), 'Assigned', $hotel->id());
        $notAssigned = PropertyMother::from(PropertyId::generate(), 'Not assigned', $hotel->id());
        $assignedLink = TripCalculationLinkMother::fromPropertyIdAndAccount((string) $assigned->id(), (string) $hotel->id());
        $notAssignedLink = TripCalculationLinkMother::fromPropertyIdAndAccount((string) $notAssigned->id(), (string) $hotel->id());
        $assignedCalculation = TripCalculationMother::fromLinkId((string) $assignedLink->id());
        $notAssignedCalculation = TripCalculationMother::fromLinkId((string) $notAssignedLink->id());

        $scope = PropertyTripDistributedContributionsResolverMother::create(
            [$distributor, $hotel],
            [$assigned, $notAssigned],
            [DistributorAssignmentMother::forProperty($distributor->id(), $assigned->id())],
            [$assignedLink, $notAssignedLink],
            [$assignedCalculation, $notAssignedCalculation],
        )->scopeFor($distributor->id());

        $this->assertTrue($scope->includes(ProjectContributionMother::pending($hotel->id(), originId: (string) $assignedCalculation->id())));
        $this->assertTrue($scope->includes(ProjectContributionMother::pending($hotel->id(), originId: (string) $assigned->id(), origin: ProjectContributionOrigin::Property)));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending($hotel->id(), originId: (string) $notAssignedCalculation->id())));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending($hotel->id(), originId: (string) $notAssigned->id(), origin: ProjectContributionOrigin::Property)));
    }

    public function testScopeIsEmptyForAccountsThatAreNotDistributors(): void
    {
        $account = AccountMother::fromNameAndId('Plain', 'plain');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');

        $scope = PropertyTripDistributedContributionsResolverMother::create(
            [$account, $hotel],
            [],
            [DistributorAssignmentMother::forAccount($account->id(), $hotel->id())],
        )->scopeFor($account->id());

        $this->assertTrue($scope->isEmpty());
    }

    public function testScopeSkipsAccountsWithoutPropertyTripCalculators(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::withFeatures('Hotel', 'hotel', new AccountFeatures(propertyTripCalculators: false));

        $scope = PropertyTripDistributedContributionsResolverMother::create(
            [$distributor, $hotel],
            [],
            [DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id())],
        )->scopeFor($distributor->id());

        $this->assertFalse($scope->includes(ProjectContributionMother::pending($hotel->id())));
    }

    public function testPropertyScopeOfAnOwnProperty(): void
    {
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());
        $link = TripCalculationLinkMother::fromPropertyIdAndAccount((string) $property->id(), (string) $hotel->id());
        $calculation = TripCalculationMother::fromLinkId((string) $link->id());

        $scope = PropertyTripDistributedContributionsResolverMother::create([$hotel], [$property], [], [$link], [$calculation])
            ->propertyScopeFor($hotel->id(), $property->id(), false);

        $this->assertTrue($scope->includes(ProjectContributionMother::pending($hotel->id(), originId: (string) $calculation->id())));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending($hotel->id())));
    }

    public function testPropertyScopeOfADistributedPropertyRequiresIncludingDistributed(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());

        $sut = PropertyTripDistributedContributionsResolverMother::create(
            [$distributor, $hotel],
            [$property],
            [DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id())],
        );

        $this->assertFalse($sut->propertyScopeFor($distributor->id(), $property->id(), true)->isEmpty());
        $this->assertTrue($sut->propertyScopeFor($distributor->id(), $property->id(), false)->isEmpty());
    }

    public function testPropertyScopeOfAForeignPropertyIsEmpty(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());

        $sut = PropertyTripDistributedContributionsResolverMother::create([$distributor, $hotel], [$property]);

        $this->assertTrue($sut->propertyScopeFor($distributor->id(), $property->id(), true)->isEmpty());
        $this->assertTrue($sut->propertyScopeFor($distributor->id(), PropertyId::generate(), true)->isEmpty());
    }

    public function testDistributesContributionsOfTripCalculationsOfDistributedProperties(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());
        $link = TripCalculationLinkMother::fromPropertyIdAndAccount((string) $property->id(), (string) $hotel->id());
        $calculation = TripCalculationMother::fromLinkId((string) $link->id());

        $sut = PropertyTripDistributedContributionsResolverMother::create(
            [$distributor, $hotel],
            [$property],
            [DistributorAssignmentMother::forProperty($distributor->id(), $property->id())],
            [$link],
            [$calculation],
        );

        $this->assertTrue($sut->distributes($distributor->id(), ProjectContributionMother::pending($hotel->id(), originId: (string) $calculation->id())));
        $this->assertTrue($sut->distributes($distributor->id(), ProjectContributionMother::pending($hotel->id(), originId: (string) $property->id(), origin: ProjectContributionOrigin::Property)));
        $this->assertFalse($sut->distributes($distributor->id(), ProjectContributionMother::pending($hotel->id())));
        $this->assertFalse($sut->distributes($distributor->id(), ProjectContributionMother::pending(AccountId::generate(), originId: (string) $calculation->id())));
        $this->assertFalse($sut->distributes($distributor->id(), ProjectContributionMother::pending($hotel->id(), originId: (string) $property->id(), origin: ProjectContributionOrigin::Account)));
    }
}
