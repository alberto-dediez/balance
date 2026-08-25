<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLeg;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;

final class OriginFlowsCalculator
{
    private const int COORDINATE_PRECISION = 2;

    /**
     * @param list<TripCalculation> $tripCalculations
     *
     * @return list<OriginFlow>
     */
    public function forTripCalculations(array $tripCalculations): array
    {
        $rows = [];

        foreach ($tripCalculations as $tripCalculation) {
            $latitude = $tripCalculation->originLatitude();
            $longitude = $tripCalculation->originLongitude();

            if (null === $latitude || null === $longitude) {
                continue;
            }

            $rows[] = [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'label' => $tripCalculation->originLabel() ?? '',
                'travellers' => max(1, $tripCalculation->people() ?? 1),
                'km' => $this->legsDistanceKm($tripCalculation->legs()),
                'emissions' => $tripCalculation->mobilityEmissions(),
            ];
        }

        return $this->group($rows);
    }

    /**
     * @param list<AttendeeSurveyResponse> $responses
     *
     * @return list<OriginFlow>
     */
    public function forAttendeeSurveyResponses(array $responses): array
    {
        $rows = [];

        foreach ($responses as $response) {
            $latitude = $response->originLatitude();
            $longitude = $response->originLongitude();

            if (null === $latitude || null === $longitude) {
                continue;
            }

            $rows[] = [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'label' => $response->originLabel() ?? '',
                'travellers' => 1,
                'km' => $this->legsDistanceKm($response->legsOfKind(LegKind::Intercity)),
                'emissions' => $response->mobilityEmissions(),
            ];
        }

        return $this->group($rows);
    }

    /**
     * @param list<array{latitude: float, longitude: float, label: string, travellers: int, km: float, emissions: Emissions}> $rows
     *
     * @return list<OriginFlow>
     */
    private function group(array $rows): array
    {
        /** @var array<string, array{latitude: float, longitude: float, labels: array<string, int>, responses: int, travellers: int, km: float, emissions: Emissions}> $groups */
        $groups = [];

        foreach ($rows as $row) {
            $latitude = round($row['latitude'], self::COORDINATE_PRECISION);
            $longitude = round($row['longitude'], self::COORDINATE_PRECISION);
            $key = $latitude . ':' . $longitude;

            $group = $groups[$key] ?? [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'labels' => [],
                'responses' => 0,
                'travellers' => 0,
                'km' => 0.0,
                'emissions' => new Emissions(),
            ];

            if ('' !== $row['label']) {
                $group['labels'][$row['label']] = ($group['labels'][$row['label']] ?? 0) + 1;
            }

            ++$group['responses'];
            $group['travellers'] += $row['travellers'];
            $group['km'] += $row['km'];
            $group['emissions'] = $group['emissions']->add($row['emissions']);

            $groups[$key] = $group;
        }

        $flows = [];

        foreach ($groups as $group) {
            $flows[] = new OriginFlow(
                latitude: $group['latitude'],
                longitude: $group['longitude'],
                label: $this->mostFrequentLabel($group['labels']),
                responses: $group['responses'],
                travellers: $group['travellers'],
                km: $group['km'] / $group['responses'],
                emissions: $group['emissions'],
            );
        }

        usort($flows, static fn(OriginFlow $a, OriginFlow $b): int => $b->travellers <=> $a->travellers);

        return $flows;
    }

    /** @param list<TripLeg> $legs */
    private function legsDistanceKm(array $legs): float
    {
        $km = 0.0;

        foreach ($legs as $leg) {
            $km += $leg->distanceKm() ?? 0.0;
        }

        return $km;
    }

    /** @param array<string, int> $labels */
    private function mostFrequentLabel(array $labels): string
    {
        if ([] === $labels) {
            return '';
        }

        arsort($labels);

        return (string) array_key_first($labels);
    }
}
