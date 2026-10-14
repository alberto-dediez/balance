<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\StorageSync;

use App\Library\Domain\Model\StorageSync\StorageSyncTask;

class StorageSyncTaskMother
{
    public static function forPath(string $path): StorageSyncTask
    {
        return new StorageSyncTask($path);
    }

    public static function forPathRegisteredAt(string $path, \DateTimeImmutable $at): StorageSyncTask
    {
        return new StorageSyncTask($path, $at);
    }
}
