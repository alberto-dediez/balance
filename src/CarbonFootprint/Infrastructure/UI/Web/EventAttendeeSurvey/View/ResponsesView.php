<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventAttendeeSurvey\View;

use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsResponse;
use App\CarbonFootprint\Infrastructure\UI\Web\Chart\TripChartBuilder;
use App\EventOperations\Domain\Model\Event\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResponsesView
{
    public function __construct(
        private TripChartBuilder $chartBuilder,
        private TranslatorInterface $translator,
    ) {}

    /** @return array<string, mixed> */
    public function build(Event $event, ?GetAttendeeSurveyStatsResponse $stats): array
    {
        if (null === $stats) {
            return ['event' => $event, 'stats' => null];
        }

        return [
            'event' => $event,
            'stats' => $stats,
            'trendChart' => $this->chartBuilder->trend($stats->responsesByDay),
            'emissionsSplitChart' => $this->chartBuilder->emissionsSplit($stats->mobilityEmissions, $stats->overnightEmissions),
            'mobilityKmChart' => $this->chartBuilder->mobilityKm($stats->mobilityBreakdown),
            'mobilityEmissionsChart' => $this->chartBuilder->mobilityEmissions($stats->mobilityBreakdown),
            'distanceSourceChart' => $this->chartBuilder->distanceSources($stats->legsByDistanceSource),
            'contributionsChart' => $this->chartBuilder->contributions($stats->contributions->aggregation),
            'originsChart' => $this->chartBuilder->counts($stats->topOrigins),
            'profileChart' => $this->chartBuilder->counts($this->profile($stats)),
        ];
    }

    /** @return array<string, int> */
    private function profile(GetAttendeeSurveyStatsResponse $stats): array
    {
        return [
            $this->translator->trans('De otra ciudad') => $stats->fromAnotherCityCount,
            $this->translator->trans('Con alojamiento') => $stats->usedHotelCount,
            $this->translator->trans('Movilidad urbana') => $stats->movedWithinCityCount,
            $this->translator->trans('Multimodales') => $stats->multimodalResponsesCount,
        ];
    }
}
