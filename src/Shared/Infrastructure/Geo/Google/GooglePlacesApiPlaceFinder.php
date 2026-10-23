<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Geo\Google;

use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPlace;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Domain\Model\Geo\PlaceSearchArea;
use App\Shared\Domain\Model\Geo\PlaceSuggestion;
use App\Shared\Domain\Model\Locale\Locale;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GooglePlacesApiPlaceFinder implements PlaceFinder
{
    private const string AUTOCOMPLETE_URL = 'https://places.googleapis.com/v1/places:autocomplete';

    private const string DETAILS_URL = 'https://places.googleapis.com/v1/places/';

    private const array AREA_COMPONENT_TYPES = [
        'country',
        'administrative_area_level_1',
        'administrative_area_level_2',
        'administrative_area_level_3',
        'administrative_area_level_4',
        'administrative_area_level_5',
        'locality',
        'postal_town',
        'sublocality',
        'sublocality_level_1',
        'postal_code',
        'colloquial_area',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly bool $enabled = false,
        private readonly string $region = 'es',
    ) {}

    public function isOperative(): bool
    {
        return $this->enabled && '' !== $this->apiKey;
    }

    public function suggest(string $query, PlaceKind $kind, Locale $locale, ?PlaceSearchArea $area = null): array
    {
        if (!$this->isOperative()) {
            return [];
        }

        $body = [
            'input' => $query,
            'languageCode' => $locale->value,
            'regionCode' => strtoupper($this->region),
        ];

        $includedPrimaryTypes = $this->includedPrimaryTypes($kind);

        if ([] !== $includedPrimaryTypes) {
            $body['includedPrimaryTypes'] = $includedPrimaryTypes;
        }

        if (null !== $area && [] !== $area->regionCodes) {
            $body['includedRegionCodes'] = $area->regionCodes;
        }

        if (null !== $area?->bounds) {
            $body['locationRestriction'] = [
                'rectangle' => [
                    'low' => ['latitude' => $area->bounds->low->latitude, 'longitude' => $area->bounds->low->longitude],
                    'high' => ['latitude' => $area->bounds->high->latitude, 'longitude' => $area->bounds->high->longitude],
                ],
            ];
        }

        try {
            $response = $this->httpClient->request('POST', self::AUTOCOMPLETE_URL, [
                'headers' => ['X-Goog-Api-Key' => $this->apiKey],
                'json' => $body,
            ]);

            /** @var array<mixed> $data */
            $data = $response->toArray();
        } catch (ExceptionInterface $e) {
            $this->logger->warning('Google Places autocomplete request failed', ['exception' => $e]);

            return [];
        }

        $suggestions = [];

        /** @var array<mixed> $suggestion */
        foreach ($data['suggestions'] ?? [] as $suggestion) {
            $prediction = $suggestion['placePrediction'] ?? null;

            if (!is_array($prediction) || !isset($prediction['placeId'])) {
                continue;
            }

            $suggestions[] = new PlaceSuggestion(
                reference: (string) $prediction['placeId'],
                label: (string) ($prediction['text']['text'] ?? $prediction['placeId']),
                secondaryLabel: isset($prediction['structuredFormat']['secondaryText']['text'])
                    ? (string) $prediction['structuredFormat']['secondaryText']['text']
                    : null,
            );
        }

        return $suggestions;
    }

    public function resolve(string $reference, Locale $locale): ?GeoPlace
    {
        if (!$this->isOperative() || '' === $reference) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::DETAILS_URL . rawurlencode($reference), [
                'headers' => [
                    'X-Goog-Api-Key' => $this->apiKey,
                    'X-Goog-FieldMask' => 'id,displayName,formattedAddress,location,viewport,addressComponents',
                ],
                'query' => ['languageCode' => $locale->value],
            ]);

            /** @var array<mixed> $data */
            $data = $response->toArray();
        } catch (ExceptionInterface $e) {
            $this->logger->warning('Google Places details request failed', ['exception' => $e]);

            return null;
        }

        $latitude = $data['location']['latitude'] ?? null;
        $longitude = $data['location']['longitude'] ?? null;

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        /** @var list<array<mixed>> $components */
        $components = is_array($data['addressComponents'] ?? null) ? array_values($data['addressComponents']) : [];

        return new GeoPlace(
            reference: (string) ($data['id'] ?? $reference),
            label: (string) ($data['formattedAddress'] ?? $data['displayName']['text'] ?? $reference),
            point: new GeoPoint((float) $latitude, (float) $longitude),
            name: isset($data['displayName']['text']) ? (string) $data['displayName']['text'] : null,
            viewport: $this->viewport($data['viewport'] ?? null),
            areaNames: $this->areaNames($components),
            countryCode: $this->countryCode($components),
        );
    }

    private function viewport(mixed $viewport): ?GeoBounds
    {
        if (!is_array($viewport)) {
            return null;
        }

        $low = $this->point($viewport['low'] ?? null);
        $high = $this->point($viewport['high'] ?? null);

        if (null === $low || null === $high) {
            return null;
        }

        return new GeoBounds($low, $high);
    }

    private function point(mixed $location): ?GeoPoint
    {
        if (!is_array($location) || !is_numeric($location['latitude'] ?? null) || !is_numeric($location['longitude'] ?? null)) {
            return null;
        }

        return new GeoPoint((float) $location['latitude'], (float) $location['longitude']);
    }

    /**
     * @param list<array<mixed>> $components
     *
     * @return list<string>
     */
    private function areaNames(array $components): array
    {
        $names = [];

        foreach ($components as $component) {
            $types = is_array($component['types'] ?? null) ? $component['types'] : [];

            if ([] === array_intersect(self::AREA_COMPONENT_TYPES, $types) || !isset($component['longText'])) {
                continue;
            }

            $names[] = (string) $component['longText'];
        }

        return array_values(array_unique($names));
    }

    /** @param list<array<mixed>> $components */
    private function countryCode(array $components): ?string
    {
        foreach ($components as $component) {
            $types = is_array($component['types'] ?? null) ? $component['types'] : [];

            if (in_array('country', $types, true) && isset($component['shortText'])) {
                return (string) $component['shortText'];
            }
        }

        return null;
    }

    /** @return list<string> */
    private function includedPrimaryTypes(PlaceKind $kind): array
    {
        return match ($kind) {
            PlaceKind::City => ['locality', 'postal_town'],
            PlaceKind::Lodging => ['lodging'],
            PlaceKind::Region => ['(regions)'],
            PlaceKind::Any => [],
        };
    }
}
