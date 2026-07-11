<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAttendeeSurveyRepository implements AttendeeSurveyRepository
{
    /** @param array<string, AttendeeSurvey> $attendeeSurveys */
    public function __construct(private array $attendeeSurveys = []) {}

    public function add(AttendeeSurvey $attendeeSurvey): void
    {
        $this->attendeeSurveys[(string) $attendeeSurvey->id()] = $attendeeSurvey;
    }

    public function find(AttendeeSurveyId $attendeeSurveyId): ?AttendeeSurvey
    {
        return $this->attendeeSurveys[(string) $attendeeSurveyId] ?? null;
    }

    public function findByEventId(EventId $eventId): ?AttendeeSurvey
    {
        foreach ($this->attendeeSurveys as $attendeeSurvey) {
            $subjectId = $attendeeSurvey->eventId();

            if ($subjectId instanceof EventId && $subjectId->equals($eventId)) {
                return $attendeeSurvey;
            }
        }

        return null;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?AttendeeSurvey
    {
        foreach ($this->attendeeSurveys as $attendeeSurvey) {
            if (hash_equals($attendeeSurvey->token(), $token)) {
                return $attendeeSurvey;
            }
        }

        return null;
    }
}
