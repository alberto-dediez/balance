<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPoint;

final class TripCalculationLinkDestinationUpdated extends DomainEvent
{
    protected static string $eventName = 'tripCalculationLinkDestinationUpdated';

    /**
     * @param list<CountryId>  $countryIds
     * @param list<PropertyId> $propertyIds
     */
    public function __construct(
        private TripCalculationLinkId $tripCalculationLinkId,
        private TripDestinationMode $mode,
        private TripDestinationPlaceKind $placeKind,
        private array $countryIds,
        private array $propertyIds,
        private ?string $regionReference,
        private ?string $regionLabel,
        private ?GeoBounds $regionBounds,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function tripCalculationLinkId(): TripCalculationLinkId
    {
        return $this->tripCalculationLinkId;
    }

    public function mode(): TripDestinationMode
    {
        return $this->mode;
    }

    public function placeKind(): TripDestinationPlaceKind
    {
        return $this->placeKind;
    }

    /** @return list<CountryId> */
    public function countryIds(): array
    {
        return $this->countryIds;
    }

    /** @return list<PropertyId> */
    public function propertyIds(): array
    {
        return $this->propertyIds;
    }

    public function regionReference(): ?string
    {
        return $this->regionReference;
    }

    public function regionLabel(): ?string
    {
        return $this->regionLabel;
    }

    public function regionBounds(): ?GeoBounds
    {
        return $this->regionBounds;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->tripCalculationLinkId();
    }

    public function payload(): array
    {
        return [
            'tripCalculationLinkId' => (string) $this->tripCalculationLinkId,
            'mode' => $this->mode->value,
            'placeKind' => $this->placeKind->value,
            'countryIds' => array_map(static fn(CountryId $countryId): string => (string) $countryId, $this->countryIds),
            'propertyIds' => array_map(static fn(PropertyId $propertyId): string => (string) $propertyId, $this->propertyIds),
            'regionReference' => $this->regionReference,
            'regionLabel' => $this->regionLabel,
            'regionLowLatitude' => $this->regionBounds?->low->latitude,
            'regionLowLongitude' => $this->regionBounds?->low->longitude,
            'regionHighLatitude' => $this->regionBounds?->high->latitude,
            'regionHighLongitude' => $this->regionBounds?->high->longitude,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        /** @var list<string> $countryIds */
        $countryIds = $payload['countryIds'] ?? [];

        /** @var list<string> $propertyIds */
        $propertyIds = $payload['propertyIds'] ?? [];

        return new self(
            tripCalculationLinkId: new TripCalculationLinkId($payload['tripCalculationLinkId']),
            mode: TripDestinationMode::from($payload['mode']),
            placeKind: TripDestinationPlaceKind::from($payload['placeKind']),
            countryIds: array_map(static fn(string $countryId): CountryId => new CountryId($countryId), $countryIds),
            propertyIds: array_map(static fn(string $propertyId): PropertyId => new PropertyId($propertyId), $propertyIds),
            regionReference: null !== ($payload['regionReference'] ?? null) ? (string) $payload['regionReference'] : null,
            regionLabel: null !== ($payload['regionLabel'] ?? null) ? (string) $payload['regionLabel'] : null,
            regionBounds: self::boundsFromPrimitives($payload),
            id: $id,
            occurredOn: $occurredOn,
        );
    }

    /** @param array<string, mixed> $payload */
    private static function boundsFromPrimitives(array $payload): ?GeoBounds
    {
        $lowLatitude = $payload['regionLowLatitude'] ?? null;
        $lowLongitude = $payload['regionLowLongitude'] ?? null;
        $highLatitude = $payload['regionHighLatitude'] ?? null;
        $highLongitude = $payload['regionHighLongitude'] ?? null;

        if (!is_numeric($lowLatitude) || !is_numeric($lowLongitude) || !is_numeric($highLatitude) || !is_numeric($highLongitude)) {
            return null;
        }

        return new GeoBounds(
            new GeoPoint((float) $lowLatitude, (float) $lowLongitude),
            new GeoPoint((float) $highLatitude, (float) $highLongitude),
        );
    }
}
