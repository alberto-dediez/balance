<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationPlaceKind;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Geo\GeoBounds;

class TripCalculationLinkMother
{
    public static function registeredTripCalculationLink(): TripCalculationLink
    {
        return self::fromPropertyId((string) PropertyId::generate());
    }

    public static function fromPropertyId(string $propertyId): TripCalculationLink
    {
        return self::from(propertyId: $propertyId, id: (string) TripCalculationLinkId::generate());
    }

    public static function fromPropertyIdAndAccount(string $propertyId, string $accountId): TripCalculationLink
    {
        return self::from(
            propertyId: $propertyId,
            id: (string) TripCalculationLinkId::generate(),
            accountId: $accountId,
        );
    }

    public static function fromPropertyIdAndScope(string $propertyId, TripCalculationScope $scope): TripCalculationLink
    {
        return self::from(
            propertyId: $propertyId,
            id: (string) TripCalculationLinkId::generate(),
            scope: $scope,
        );
    }

    public static function from(
        string $propertyId,
        string $id,
        ?string $accountId = null,
        ?TripCalculationScope $scope = null,
    ): TripCalculationLink {
        $tripCalculationLink = new TripCalculationLink(id: new TripCalculationLinkId($id));
        $tripCalculationLink->register(
            propertyId: new PropertyId($propertyId),
            accountId: new AccountId($accountId ?? 'account-id'),
            token: TripCalculationLink::generateToken(),
        );

        if (null !== $scope) {
            $tripCalculationLink->updateScope($scope);
        }

        return $tripCalculationLink;
    }

    public static function forAccount(string $accountId, ?TripCalculationScope $scope = null, ?string $id = null): TripCalculationLink
    {
        $tripCalculationLink = new TripCalculationLink(id: new TripCalculationLinkId($id ?? (string) TripCalculationLinkId::generate()));
        $tripCalculationLink->registerForAccount(
            accountId: new AccountId($accountId),
            token: TripCalculationLink::generateToken(),
        );

        if (null !== $scope) {
            $tripCalculationLink->updateScope($scope);
        }

        return $tripCalculationLink;
    }

    /**
     * @param list<CountryId>  $countryIds
     * @param list<PropertyId> $propertyIds
     */
    public static function forAccountWithDestination(
        string $accountId,
        TripDestinationMode $mode,
        array $countryIds = [],
        array $propertyIds = [],
        ?string $regionReference = null,
        ?string $regionLabel = null,
        ?GeoBounds $regionBounds = null,
        TripDestinationPlaceKind $placeKind = TripDestinationPlaceKind::City,
        ?TripCalculationScope $scope = null,
    ): TripCalculationLink {
        $tripCalculationLink = self::forAccount($accountId, $scope);
        $tripCalculationLink->updateDestination(
            mode: $mode,
            placeKind: $placeKind,
            countryIds: $countryIds,
            propertyIds: $propertyIds,
            regionReference: $regionReference,
            regionLabel: $regionLabel,
            regionBounds: $regionBounds,
        );

        return $tripCalculationLink;
    }

    public static function random(): TripCalculationLink
    {
        return self::registeredTripCalculationLink();
    }
}
