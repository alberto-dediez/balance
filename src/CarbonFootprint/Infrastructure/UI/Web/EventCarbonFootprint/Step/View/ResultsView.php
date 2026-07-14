<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsResponse;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsResponse;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\EventOperations\Domain\Model\Event\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResultsView
{
    public function __construct(private TranslatorInterface $translator) {}

    /** @return array<string, mixed> */
    public function build(
        Event $event,
        EventCarbonFootprint $eventCarbonFootprint,
        GetResultsResponse $results,
        ?GetAttendeeSurveyStatsResponse $surveyStats,
    ): array {
        $attendeeSurvey = $surveyStats?->attendeeSurvey;
        $realAttendees = $attendeeSurvey?->realAttendees();
        $attendeesUsed = ($realAttendees ?? 0) > 0 ? $realAttendees : $event->estimatedAttendees();

        return [
            'event' => $event,
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'results' => $results,
            'attendeesUsed' => $attendeesUsed,
            'surveyStats' => $surveyStats,
            'sourceChart' => $this->sourceChart($results),
            'phaseChart' => $this->phaseChart($results),
            'phaseSourceSeries' => $this->phaseSourceSeries($results),
            'phaseCategories' => array_map(
                fn(Phase $phase) => $this->translator->trans($phase->value, [], 'phase'),
                Phase::cases(),
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function sourceChart(GetResultsResponse $results): array
    {
        $labels = [];
        $values = [];

        foreach (Source::cases() as $source) {
            $co2eq = (float) $results->emissionsBySource[$source->value]->co2eq();

            if ($co2eq <= 0.0) {
                continue;
            }

            $labels[] = $this->translator->trans($source->value, [], 'source');
            $values[] = $co2eq;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** @return array<string, mixed> */
    private function phaseChart(GetResultsResponse $results): array
    {
        $labels = [];
        $values = [];

        foreach (Phase::cases() as $phase) {
            $labels[] = $this->translator->trans($phase->value, [], 'phase');
            $values[] = (float) $results->emissionsByPhase[$phase->value]->co2eq();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** @return list<array{name: string, data: list<float>}> */
    private function phaseSourceSeries(GetResultsResponse $results): array
    {
        $series = [];

        foreach (Source::cases() as $source) {
            $totalForSource = (float) $results->emissionsBySource[$source->value]->co2eq();

            if ($totalForSource <= 0.0) {
                continue;
            }

            $data = [];
            foreach (Phase::cases() as $phase) {
                $data[] = (float) $results->emissionsByPhaseAndSource[$phase->value][$source->value]->co2eq();
            }

            $series[] = [
                'name' => $this->translator->trans($source->value, [], 'source'),
                'data' => $data,
            ];
        }

        return $series;
    }
}
