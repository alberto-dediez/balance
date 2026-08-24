<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;

interface AttendeeSurveyResponseRepository
{
    public function add(AttendeeSurveyResponse $attendeeSurveyResponse): void;

    public function find(AttendeeSurveyResponseId $attendeeSurveyResponseId): ?AttendeeSurveyResponse;

    /** @return list<AttendeeSurveyResponse> */
    public function findByAttendeeSurveyId(AttendeeSurveyId $attendeeSurveyId): array;

    public function countByAttendeeSurveyId(AttendeeSurveyId $attendeeSurveyId): int;

    /** @param list<AttendeeSurveyId> $attendeeSurveyIds */
    public function countByAttendeeSurveyIds(array $attendeeSurveyIds): int;

    /** @return array<string, int> Number of legs of the survey keyed by the origin of their distance. */
    public function countLegsByDistanceSource(AttendeeSurveyId $attendeeSurveyId): array;

    public function countMultimodalResponses(AttendeeSurveyId $attendeeSurveyId): int;
}
