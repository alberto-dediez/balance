<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Geo\Google;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistance;
use App\CarbonFootprint\Domain\Model\Geo\RouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\Shared\Domain\Model\Geo\GeoPoint;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleRoutesApiDistanceCalculator implements RouteDistanceCalculator
{
    private const string API_URL = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly bool $enabled = false,
        private readonly string $region = 'es',
    ) {}

    public function calculate(GeoPoint $origin, GeoPoint $destination, TravelMode $mode): ?RouteDistance
    {
        $googleTravelMode = $this->googleTravelMode($mode);

        if (null === $googleTravelMode || !$this->enabled || '' === $this->apiKey) {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'X-Goog-Api-Key' => $this->apiKey,
                    'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration',
                ],
                'json' => [
                    'origin' => ['location' => ['latLng' => ['latitude' => $origin->latitude, 'longitude' => $origin->longitude]]],
                    'destination' => ['location' => ['latLng' => ['latitude' => $destination->latitude, 'longitude' => $destination->longitude]]],
                    'travelMode' => $googleTravelMode,
                    'regionCode' => strtoupper($this->region),
                ],
            ]);

            /** @var array<mixed> $data */
            $data = $response->toArray();
        } catch (ExceptionInterface $e) {
            $this->logger->warning('Google Routes API request failed', ['exception' => $e]);

            return null;
        }

        /** @var array<mixed>|null $route */
        $route = $data['routes'][0] ?? null;

        if (null === $route || !isset($route['distanceMeters'])) {
            return null;
        }

        return new RouteDistance(
            kilometers: ((float) $route['distanceMeters']) / 1000,
            durationSeconds: $this->durationSeconds($route['duration'] ?? null),
            source: DistanceSource::Route,
        );
    }

    private function googleTravelMode(TravelMode $mode): ?string
    {
        return match ($mode) {
            TravelMode::Driving => 'DRIVE',
            TravelMode::Transit => 'TRANSIT',
            TravelMode::Cycling => 'BICYCLE',
            TravelMode::Walking => 'WALK',
            TravelMode::Flight => null,
        };
    }

    private function durationSeconds(mixed $duration): ?int
    {
        if (!is_string($duration) || 1 !== preg_match('/^(\d+)s$/', $duration, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
