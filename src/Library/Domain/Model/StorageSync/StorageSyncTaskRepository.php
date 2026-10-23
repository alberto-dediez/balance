<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\StorageSync;

interface StorageSyncTaskRepository
{
    public function find(string $path): ?StorageSyncTask;

    public function save(StorageSyncTask $task): void;

    public function remove(StorageSyncTask $task): void;

    /**
     * @return list<StorageSyncTask>
     */
    public function findPending(int $limit): array;

    public function countPending(): int;
}
