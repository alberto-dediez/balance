<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessFilter;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryProcessRepository implements ProcessRepository
{
    /** @param array<string, Process> $processes */
    public function __construct(
        private array $processes = [],
    ) {}

    public function add(Process $process): void
    {
        if (isset($this->processes[(string) $process->id()])) {
            return;
        }

        $this->processes[(string) $process->id()] = $process;
    }

    public function remove(Process $process): void
    {
        if (!isset($this->processes[(string) $process->id()])) {
            return;
        }

        unset($this->processes[(string) $process->id()]);
    }

    public function find(ProcessId $processId): ?Process
    {
        return (isset($this->processes[(string) $processId]) && false == $this->processes[(string) $processId]->isRemoved())
            ? $this->processes[(string) $processId]
            : null
        ;
    }

    /** @return array<int, Process> */
    public function findByFilter(ProcessFilter $filter): array
    {
        $results = [];

        if (0 === count($this->processes)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        $way = 'ASC' === $filter->orderWay ? 1 : -1;
        $processes = array_values($this->processes);
        usort(
            $processes,
            static fn(Process $a, Process $b): int => $way * match ($filter->orderBy) {
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                'updatedAt' => $a->updatedAt() <=> $b->updatedAt(),
                default => $a->name() <=> $b->name(),
            },
        );

        foreach ($processes as $process) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $process->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $process->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $process;
        }

        return $results;
    }

    public function totalResultsByFilter(ProcessFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
