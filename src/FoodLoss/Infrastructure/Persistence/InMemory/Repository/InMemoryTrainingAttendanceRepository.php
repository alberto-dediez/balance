<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryTrainingAttendanceRepository implements TrainingAttendanceRepository
{
    /** @param array<string, TrainingAttendance> $attendances */
    public function __construct(
        private array $attendances = [],
    ) {}

    public function add(TrainingAttendance $trainingAttendance): void
    {
        $this->attendances[(string) $trainingAttendance->id()] = $trainingAttendance;
    }

    public function find(TrainingAttendanceId $trainingAttendanceId): ?TrainingAttendance
    {
        $attendance = $this->attendances[(string) $trainingAttendanceId] ?? null;

        return ($attendance && !$attendance->isRemoved()) ? $attendance : null;
    }

    /** @return array<int, TrainingAttendance> */
    public function findByFilter(TrainingAttendanceFilter $filter): array
    {
        $results = [];

        foreach ($this->attendances as $attendance) {
            if ($attendance->isRemoved()) {
                continue;
            }

            if (
                $filter->trainingSessionId instanceof TrainingSessionId
                && (string) $attendance->trainingSessionId() !== (string) $filter->trainingSessionId
            ) {
                continue;
            }

            if (
                $filter->employeeId instanceof EmployeeId
                && (string) $attendance->employeeId() !== (string) $filter->employeeId
            ) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (string) $attendance->accountId() !== (string) $filter->accountId
            ) {
                continue;
            }

            if (
                null !== $filter->filePath
                && $attendance->filePath() !== $filter->filePath
            ) {
                continue;
            }

            $results[] = $attendance;
        }

        usort($results, function (TrainingAttendance $a, TrainingAttendance $b) use ($filter): int {
            $way = 'ASC' === $filter->orderWay ? 1 : -1;

            $valA = match ($filter->orderBy) {
                'attendedAt' => $a->attendedAt(),
                default => $a->registeredAt(),
            };

            $valB = match ($filter->orderBy) {
                'attendedAt' => $b->attendedAt(),
                default => $b->registeredAt(),
            };

            return ($valA <=> $valB) * $way;
        });

        if ($filter->limit > 0) {
            return array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(TrainingAttendanceFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;

        return count($this->findByFilter($clonedFilter));
    }

    public function countByFilePath(string $filePath): int
    {
        $count = 0;

        foreach ($this->attendances as $attendance) {
            if (!$attendance->isRemoved() && $attendance->filePath() === $filePath) {
                ++$count;
            }
        }

        return $count;
    }
}
