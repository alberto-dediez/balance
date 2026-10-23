<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Persistence\InMemory\Repository;

use App\Library\Domain\Model\StorageSync\StorageSyncTask;
use App\Library\Domain\Model\StorageSync\StorageSyncTaskRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryStorageSyncTaskRepository implements StorageSyncTaskRepository
{
    /** @var array<string, StorageSyncTask> */
    private array $tasks = [];

    public function find(string $path): ?StorageSyncTask
    {
        return $this->tasks[$path] ?? null;
    }

    public function save(StorageSyncTask $task): void
    {
        $this->tasks[$task->path()] = $task;
    }

    public function remove(StorageSyncTask $task): void
    {
        unset($this->tasks[$task->path()]);
    }

    /**
     * @return list<StorageSyncTask>
     */
    public function findPending(int $limit): array
    {
        $tasks = array_values($this->tasks);

        usort(
            $tasks,
            static fn(StorageSyncTask $a, StorageSyncTask $b): int => [$a->registeredAt(), $a->path()] <=> [$b->registeredAt(), $b->path()],
        );

        return $limit > 0 ? array_slice($tasks, 0, $limit) : $tasks;
    }

    public function countPending(): int
    {
        return count($this->tasks);
    }
}
