<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\JobProgress;

interface JobProgressRepository
{
    public function save(JobProgress $jobProgress): void;

    public function find(JobProgressId $id): ?JobProgress;

    public function findByKey(JobProgressKey $key): ?JobProgress;
}
