<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\InMemory\Repository;

use App\Shared\Domain\Model\JobProgress\JobProgress;
use App\Shared\Domain\Model\JobProgress\JobProgressId;
use App\Shared\Domain\Model\JobProgress\JobProgressKey;
use App\Shared\Domain\Model\JobProgress\JobProgressRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryJobProgressRepository implements JobProgressRepository
{
    /** @param array<string, JobProgress> $jobProgresses */
    public function __construct(private array $jobProgresses = []) {}

    public function save(JobProgress $jobProgress): void
    {
        $this->jobProgresses[(string) $jobProgress->id()] = $jobProgress;
    }

    public function find(JobProgressId $id): ?JobProgress
    {
        return $this->jobProgresses[(string) $id] ?? null;
    }

    public function findByKey(JobProgressKey $key): ?JobProgress
    {
        foreach ($this->jobProgresses as $jobProgress) {
            if ((string) $jobProgress->key() === (string) $key) {
                return $jobProgress;
            }
        }

        return null;
    }
}
