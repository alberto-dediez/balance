<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Persistence\InMemory;

use App\Library\Domain\Model\StorageSync\StorageSyncTask;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryStorageSyncTaskRepository;
use App\Tests\Unit\Library\Domain\Model\StorageSync\StorageSyncTaskMother;
use PHPUnit\Framework\TestCase;

class InMemoryStorageSyncTaskRepositoryTest extends TestCase
{
    public function testSaveFindAndRemove(): void
    {
        $sut = new InMemoryStorageSyncTaskRepository();
        $task = StorageSyncTaskMother::forPath('shared/file.png');

        $sut->save($task);

        $this->assertSame($task, $sut->find('shared/file.png'));
        $this->assertNull($sut->find('shared/other.png'));

        $sut->remove($task);

        $this->assertNull($sut->find('shared/file.png'));
    }

    public function testSaveReplacesTheTaskOfTheSamePath(): void
    {
        $sut = new InMemoryStorageSyncTaskRepository();

        $sut->save(StorageSyncTaskMother::forPath('shared/file.png'));
        $replacement = StorageSyncTaskMother::forPath('shared/file.png');
        $sut->save($replacement);

        $this->assertSame(1, $sut->countPending());
        $this->assertSame($replacement, $sut->find('shared/file.png'));
    }

    public function testFindPendingIsSortedByRegistrationDateAndPath(): void
    {
        $sut = new InMemoryStorageSyncTaskRepository();

        $sut->save(StorageSyncTaskMother::forPathRegisteredAt('shared/c.png', new \DateTimeImmutable('2026-10-14 10:00:00')));
        $sut->save(StorageSyncTaskMother::forPathRegisteredAt('shared/b.png', new \DateTimeImmutable('2026-10-14 09:00:00')));
        $sut->save(StorageSyncTaskMother::forPathRegisteredAt('shared/a.png', new \DateTimeImmutable('2026-10-14 10:00:00')));

        $paths = array_map(static fn(StorageSyncTask $task): string => $task->path(), $sut->findPending(0));

        $this->assertSame(['shared/b.png', 'shared/a.png', 'shared/c.png'], $paths);
    }

    public function testFindPendingRespectsTheLimit(): void
    {
        $sut = new InMemoryStorageSyncTaskRepository();

        $sut->save(StorageSyncTaskMother::forPathRegisteredAt('shared/a.png', new \DateTimeImmutable('2026-10-14 09:00:00')));
        $sut->save(StorageSyncTaskMother::forPathRegisteredAt('shared/b.png', new \DateTimeImmutable('2026-10-14 10:00:00')));

        $pending = $sut->findPending(1);

        $this->assertCount(1, $pending);
        $this->assertSame('shared/a.png', $pending[0]->path());
    }

    public function testCountPending(): void
    {
        $sut = new InMemoryStorageSyncTaskRepository();

        $this->assertSame(0, $sut->countPending());

        $sut->save(StorageSyncTaskMother::forPath('shared/a.png'));
        $sut->save(StorageSyncTaskMother::forPath('shared/b.png'));

        $this->assertSame(2, $sut->countPending());
    }
}
