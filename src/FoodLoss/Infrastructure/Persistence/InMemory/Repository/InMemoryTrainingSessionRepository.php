<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionFilter;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryTrainingSessionRepository implements TrainingSessionRepository
{
    /** @param array<string, TrainingSession> $trainingSessions */
    public function __construct(
        private array $trainingSessions = [],
    ) {}

    public function add(TrainingSession $trainingSession): void
    {
        $this->trainingSessions[(string) $trainingSession->id()] = $trainingSession;
    }

    public function remove(TrainingSession $trainingSession): void
    {
        if (!isset($this->trainingSessions[(string) $trainingSession->id()])) {
            return;
        }

        unset($this->trainingSessions[(string) $trainingSession->id()]);
    }

    public function find(TrainingSessionId $trainingSessionId): ?TrainingSession
    {
        $trainingSession = $this->trainingSessions[(string) $trainingSessionId] ?? null;

        return ($trainingSession && false == $trainingSession->isRemoved())
            ? $trainingSession
            : null
        ;
    }

    /** @return array<int, TrainingSession> */
    public function findByFilter(TrainingSessionFilter $filter): array
    {
        $results = [];

        if (0 === count($this->trainingSessions)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->trainingSessions as $trainingSession) {
            if (true == $trainingSession->isRemoved()) {
                continue;
            }

            if (
                !empty($filter->title)
                && !str_contains(strtolower($trainingSession->title()), strtolower($filter->title))
            ) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (string) $trainingSession->accountId() !== (string) $filter->accountId
            ) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $trainingSession;
        }

        return $results;
    }

    public function totalResultsByFilter(TrainingSessionFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
