<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAttendeeSurveyResponseRepository implements AttendeeSurveyResponseRepository
{
    /** @param array<string, AttendeeSurveyResponse> $attendeeSurveyResponses */
    public function __construct(private array $attendeeSurveyResponses = []) {}

    public function add(AttendeeSurveyResponse $attendeeSurveyResponse): void
    {
        $this->attendeeSurveyResponses[(string) $attendeeSurveyResponse->id()] = $attendeeSurveyResponse;
    }

    public function find(AttendeeSurveyResponseId $attendeeSurveyResponseId): ?AttendeeSurveyResponse
    {
        return $this->attendeeSurveyResponses[(string) $attendeeSurveyResponseId] ?? null;
    }

    /** @return list<AttendeeSurveyResponse> */
    public function findByAttendeeSurveyId(AttendeeSurveyId $attendeeSurveyId): array
    {
        $results = [];

        foreach ($this->attendeeSurveyResponses as $attendeeSurveyResponse) {
            $subjectId = $attendeeSurveyResponse->attendeeSurveyId();

            if ($subjectId instanceof AttendeeSurveyId && $subjectId->equals($attendeeSurveyId)) {
                $results[] = $attendeeSurveyResponse;
            }
        }

        return $results;
    }

    public function countByAttendeeSurveyId(AttendeeSurveyId $attendeeSurveyId): int
    {
        return count($this->findByAttendeeSurveyId($attendeeSurveyId));
    }

    /** @return array<string, int> */
    public function countLegsByDistanceSource(AttendeeSurveyId $attendeeSurveyId): array
    {
        $counts = [];

        foreach ($this->findByAttendeeSurveyId($attendeeSurveyId) as $attendeeSurveyResponse) {
            foreach ($attendeeSurveyResponse->legs() as $leg) {
                $source = $leg->distanceSource()->value;
                $counts[$source] = ($counts[$source] ?? 0) + 1;
            }
        }

        return $counts;
    }

    public function countMultimodalResponses(AttendeeSurveyId $attendeeSurveyId): int
    {
        $count = 0;

        foreach ($this->findByAttendeeSurveyId($attendeeSurveyId) as $attendeeSurveyResponse) {
            if (count($attendeeSurveyResponse->legsOfKind(LegKind::Intercity)) > 1
                || count($attendeeSurveyResponse->legsOfKind(LegKind::City)) > 1
            ) {
                ++$count;
            }
        }

        return $count;
    }
}
