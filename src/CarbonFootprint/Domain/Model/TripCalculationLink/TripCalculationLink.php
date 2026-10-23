<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPoint;
use Doctrine\Common\Collections\Collection;

class TripCalculationLink implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<TripCalculationLinkDestinationCountry>|Collection<int, TripCalculationLinkDestinationCountry> */
    private iterable $destinationCountries = [];

    /** @var iterable<TripCalculationLinkDestinationProperty>|Collection<int, TripCalculationLinkDestinationProperty> */
    private iterable $destinationProperties = [];

    /** @param array<DomainEvent> $events */
    public function __construct(
        private TripCalculationLinkId $id,
        private ?PropertyId $propertyId = null,
        private ?AccountId $accountId = null,
        #[\SensitiveParameter]
        private string $token = '',
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private TripCalculationScope $scope = TripCalculationScope::Both,
        private TripDestinationMode $destinationMode = TripDestinationMode::Free,
        private TripDestinationPlaceKind $destinationPlaceKind = TripDestinationPlaceKind::City,
        private ?string $destinationRegionReference = null,
        private ?string $destinationRegionLabel = null,
        private ?float $destinationRegionLowLatitude = null,
        private ?float $destinationRegionLowLongitude = null,
        private ?float $destinationRegionHighLatitude = null,
        private ?float $destinationRegionHighLongitude = null,
    ) {
        $this->events = $events;
    }

    public function id(): TripCalculationLinkId
    {
        return $this->id;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function scope(): TripCalculationScope
    {
        return $this->scope;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function isForProperty(): bool
    {
        return null !== $this->propertyId;
    }

    public function destinationMode(): TripDestinationMode
    {
        return $this->destinationMode;
    }

    public function destinationPlaceKind(): TripDestinationPlaceKind
    {
        return $this->destinationPlaceKind;
    }

    public function destinationRegionReference(): ?string
    {
        return $this->destinationRegionReference;
    }

    public function destinationRegionLabel(): ?string
    {
        return $this->destinationRegionLabel;
    }

    public function destinationRegionBounds(): ?GeoBounds
    {
        if (
            null === $this->destinationRegionLowLatitude
            || null === $this->destinationRegionLowLongitude
            || null === $this->destinationRegionHighLatitude
            || null === $this->destinationRegionHighLongitude
        ) {
            return null;
        }

        return new GeoBounds(
            new GeoPoint($this->destinationRegionLowLatitude, $this->destinationRegionLowLongitude),
            new GeoPoint($this->destinationRegionHighLatitude, $this->destinationRegionHighLongitude),
        );
    }

    /** @return list<CountryId> */
    public function destinationCountryIds(): array
    {
        return array_map(
            static fn(TripCalculationLinkDestinationCountry $country): CountryId => $country->countryId(),
            $this->destinationCountries(),
        );
    }

    /** @return list<PropertyId> */
    public function destinationPropertyIds(): array
    {
        return array_map(
            static fn(TripCalculationLinkDestinationProperty $property): PropertyId => $property->propertyId(),
            $this->destinationProperties(),
        );
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function register(
        PropertyId $propertyId,
        AccountId $accountId,
        #[\SensitiveParameter]
        string $token,
    ): void {
        $this->recordApplyAndPublish(
            new TripCalculationLinkRegistered(
                tripCalculationLinkId: $this->id(),
                propertyId: $propertyId,
                accountId: $accountId,
                token: $token,
            ),
        );
    }

    public function registerForAccount(
        AccountId $accountId,
        #[\SensitiveParameter]
        string $token,
    ): void {
        $this->recordApplyAndPublish(
            new TripCalculationLinkRegistered(
                tripCalculationLinkId: $this->id(),
                propertyId: null,
                accountId: $accountId,
                token: $token,
            ),
        );
    }

    /**
     * @param list<CountryId>  $countryIds
     * @param list<PropertyId> $propertyIds
     */
    public function updateDestination(
        TripDestinationMode $mode,
        TripDestinationPlaceKind $placeKind,
        array $countryIds,
        array $propertyIds,
        ?string $regionReference,
        ?string $regionLabel,
        ?GeoBounds $regionBounds,
    ): void {
        if ($this->isForProperty()) {
            throw new TripCalculationLinkDestinationNotConfigurableException();
        }

        $countryIds = TripDestinationMode::Countries === $mode ? self::uniqueIds($countryIds) : [];
        $propertyIds = TripDestinationMode::SelectedProperties === $mode ? self::uniqueIds($propertyIds) : [];

        if (TripDestinationMode::Region !== $mode) {
            $regionReference = null;
            $regionLabel = null;
            $regionBounds = null;
        }

        if (TripDestinationMode::Countries === $mode && ([] === $countryIds || count($countryIds) > 15)) {
            throw new \InvalidArgumentException('Debes elegir entre 1 y 15 países.');
        }

        if (TripDestinationMode::Region === $mode && (empty($regionReference) || empty($regionLabel) || null === $regionBounds)) {
            throw new \InvalidArgumentException('Debes elegir una región.');
        }

        if (TripDestinationMode::SelectedProperties === $mode && [] === $propertyIds) {
            throw new \InvalidArgumentException('Debes elegir al menos una propiedad.');
        }

        $this->recordApplyAndPublish(
            new TripCalculationLinkDestinationUpdated(
                tripCalculationLinkId: $this->id(),
                mode: $mode,
                placeKind: $placeKind,
                countryIds: $countryIds,
                propertyIds: $propertyIds,
                regionReference: $regionReference,
                regionLabel: $regionLabel,
                regionBounds: $regionBounds,
            ),
        );
    }

    public function updateScope(TripCalculationScope $scope): void
    {
        $this->recordApplyAndPublish(
            new TripCalculationLinkScopeUpdated(
                tripCalculationLinkId: $this->id(),
                scope: $scope,
            ),
        );
    }

    public function applyTripCalculationLinkRegistered(TripCalculationLinkRegistered $event): void
    {
        $this->propertyId = $event->propertyId();
        $this->accountId = $event->accountId();
        $this->token = $event->token();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyTripCalculationLinkScopeUpdated(TripCalculationLinkScopeUpdated $event): void
    {
        $this->scope = $event->scope();
    }

    public function applyTripCalculationLinkDestinationUpdated(TripCalculationLinkDestinationUpdated $event): void
    {
        $this->destinationMode = $event->mode();
        $this->destinationPlaceKind = $event->placeKind();
        $this->destinationRegionReference = $event->regionReference();
        $this->destinationRegionLabel = $event->regionLabel();
        $this->destinationRegionLowLatitude = $event->regionBounds()?->low->latitude;
        $this->destinationRegionLowLongitude = $event->regionBounds()?->low->longitude;
        $this->destinationRegionHighLatitude = $event->regionBounds()?->high->latitude;
        $this->destinationRegionHighLongitude = $event->regionBounds()?->high->longitude;

        $this->destinationCountries = $this->synchronizedChildren(
            $this->destinationCountries,
            $event->countryIds(),
            static fn(TripCalculationLinkDestinationCountry $country): CountryId => $country->countryId(),
            fn(CountryId $countryId): TripCalculationLinkDestinationCountry => new TripCalculationLinkDestinationCountry(
                id: TripCalculationLinkDestinationCountryId::generate(),
                tripCalculationLink: $this,
                countryId: $countryId,
            ),
        );

        $this->destinationProperties = $this->synchronizedChildren(
            $this->destinationProperties,
            $event->propertyIds(),
            static fn(TripCalculationLinkDestinationProperty $property): PropertyId => $property->propertyId(),
            fn(PropertyId $propertyId): TripCalculationLinkDestinationProperty => new TripCalculationLinkDestinationProperty(
                id: TripCalculationLinkDestinationPropertyId::generate(),
                tripCalculationLink: $this,
                propertyId: $propertyId,
            ),
        );
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    /** @return list<TripCalculationLinkDestinationCountry> */
    private function destinationCountries(): array
    {
        return array_values(
            $this->destinationCountries instanceof Collection
                ? $this->destinationCountries->toArray()
                : (array) $this->destinationCountries,
        );
    }

    /** @return list<TripCalculationLinkDestinationProperty> */
    private function destinationProperties(): array
    {
        return array_values(
            $this->destinationProperties instanceof Collection
                ? $this->destinationProperties->toArray()
                : (array) $this->destinationProperties,
        );
    }

    /**
     * @template TId of CountryId|PropertyId
     * @template TChild of object
     *
     * @param iterable<TChild>|Collection<int, TChild> $children
     * @param list<TId>                                $ids
     * @param callable(TChild): TId                    $idOf
     * @param callable(TId): TChild                    $create
     *
     * @return iterable<TChild>|Collection<int, TChild>
     */
    private function synchronizedChildren(iterable $children, array $ids, callable $idOf, callable $create): iterable
    {
        $wanted = array_map(static fn(CountryId|PropertyId $id): string => (string) $id, $ids);
        $kept = [];

        if ($children instanceof Collection) {
            foreach ($children->toArray() as $child) {
                $childId = (string) $idOf($child);

                if (!in_array($childId, $wanted, true) || in_array($childId, $kept, true)) {
                    $children->removeElement($child);

                    continue;
                }

                $kept[] = $childId;
            }

            foreach ($ids as $id) {
                if (!in_array((string) $id, $kept, true)) {
                    $children->add($create($id));
                }
            }

            return $children;
        }

        $result = [];

        foreach ($children as $child) {
            $childId = (string) $idOf($child);

            if (in_array($childId, $wanted, true) && !in_array($childId, $kept, true)) {
                $result[] = $child;
                $kept[] = $childId;
            }
        }

        foreach ($ids as $id) {
            if (!in_array((string) $id, $kept, true)) {
                $result[] = $create($id);
            }
        }

        return $result;
    }

    /**
     * @template TId of CountryId|PropertyId
     *
     * @param list<TId> $ids
     *
     * @return list<TId>
     */
    private static function uniqueIds(array $ids): array
    {
        $unique = [];

        foreach ($ids as $id) {
            $unique[(string) $id] = $id;
        }

        return array_values($unique);
    }
}
