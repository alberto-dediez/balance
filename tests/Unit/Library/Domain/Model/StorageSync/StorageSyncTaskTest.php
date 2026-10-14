<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\StorageSync;

use PHPUnit\Framework\TestCase;

class StorageSyncTaskTest extends TestCase
{
    public function testNewTask(): void
    {
        $task = StorageSyncTaskMother::forPath('shared/file.png');

        $this->assertSame('shared/file.png', $task->path());
        $this->assertSame(0, $task->attempts());
        $this->assertNull($task->lastAttemptAt());
        $this->assertNull($task->lastError());
    }

    public function testRecordFailure(): void
    {
        $task = StorageSyncTaskMother::forPath('shared/file.png');
        $now = new \DateTimeImmutable('2026-10-14 10:00:00');

        $task->recordFailure('Bucket unavailable', $now);
        $task->recordFailure('Bucket still unavailable', $now);

        $this->assertSame(2, $task->attempts());
        $this->assertSame($now, $task->lastAttemptAt());
        $this->assertSame('Bucket still unavailable', $task->lastError());
    }

    public function testRecordFailureTruncatesTheError(): void
    {
        $task = StorageSyncTaskMother::forPath('shared/file.png');

        $task->recordFailure(str_repeat('a', 1500), new \DateTimeImmutable('NOW'));

        $this->assertSame(1000, mb_strlen((string) $task->lastError()));
    }
}
