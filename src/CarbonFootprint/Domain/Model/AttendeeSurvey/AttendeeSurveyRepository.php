<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\EventOperations\Domain\Model\Event\EventId;

interface AttendeeSurveyRepository
{
    public function add(AttendeeSurvey $attendeeSurvey): void;

    public function find(AttendeeSurveyId $attendeeSurveyId): ?AttendeeSurvey;

    public function findByEventId(EventId $eventId): ?AttendeeSurvey;

    public function findByToken(#[\SensitiveParameter] string $token): ?AttendeeSurvey;
}
