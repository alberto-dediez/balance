<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\EventOperations\Domain\Model\Event\EventId;

interface AttendeeSurveyRepository
{
    public function add(AttendeeSurvey $attendeeSurvey): void;

    public function find(AttendeeSurveyId $attendeeSurveyId): ?AttendeeSurvey;

    public function findByEventId(EventId $eventId): ?AttendeeSurvey;

    /** @return list<AttendeeSurvey> */
    public function findByFilter(AttendeeSurveyFilter $filter): array;

    public function totalResultsByFilter(AttendeeSurveyFilter $filter): int;

    public function findByToken(#[\SensitiveParameter] string $token): ?AttendeeSurvey;
}
