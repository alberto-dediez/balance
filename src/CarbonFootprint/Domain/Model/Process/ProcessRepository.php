<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Process;

interface ProcessRepository
{
    public function add(Process $process): void;

    public function remove(Process $process): void;

    public function find(ProcessId $processId): ?Process;

    /** @return array<Process> */
    public function findByFilter(ProcessFilter $filter): array;

    public function totalResultsByFilter(ProcessFilter $filter): int;
}
