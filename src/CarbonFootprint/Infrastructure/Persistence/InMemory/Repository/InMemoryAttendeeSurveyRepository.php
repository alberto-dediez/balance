<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyFilter;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
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

    /** @return list<AttendeeSurvey> */
    public function findByFilter(AttendeeSurveyFilter $filter): array
    {
        $results = [];

        foreach ($this->attendeeSurveys as $attendeeSurvey) {
            if ($this->matches($attendeeSurvey, $filter)) {
                $results[] = $attendeeSurvey;
            }
        }

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(AttendeeSurveyFilter $filter): int
    {
        $count = 0;

        foreach ($this->attendeeSurveys as $attendeeSurvey) {
            if ($this->matches($attendeeSurvey, $filter)) {
                ++$count;
            }
        }

        return $count;
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

    private function matches(AttendeeSurvey $attendeeSurvey, AttendeeSurveyFilter $filter): bool
    {
        if (null !== $filter->accountId) {
            $accountId = $attendeeSurvey->accountId();

            if (!$accountId instanceof AccountId || !$accountId->equals($filter->accountId)) {
                return false;
            }
        }

        if (null !== $filter->eventId) {
            $eventId = $attendeeSurvey->eventId();

            if (!$eventId instanceof EventId || !$eventId->equals($filter->eventId)) {
                return false;
            }
        }

        return !(null !== $filter->status && $filter->status !== $attendeeSurvey->status());
    }
}
