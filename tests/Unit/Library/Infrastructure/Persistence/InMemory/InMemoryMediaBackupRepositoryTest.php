<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Persistence\InMemory;

use App\Library\Domain\Model\Backup\MediaBackup;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaBackupRepository;
use App\Tests\Unit\Library\Domain\Model\Backup\MediaBackupMother;
use PHPUnit\Framework\TestCase;

class InMemoryMediaBackupRepositoryTest extends TestCase
{
    public function testSaveFindAndRemove(): void
    {
        $sut = new InMemoryMediaBackupRepository();
        $backup = MediaBackupMother::forPath('shared/file.png');

        $sut->save($backup);

        $this->assertSame($backup, $sut->find('shared/file.png'));
        $this->assertNull($sut->find('shared/other.png'));

        $sut->remove($backup);

        $this->assertNull($sut->find('shared/file.png'));
    }

    public function testFindByMediaId(): void
    {
        $sut = new InMemoryMediaBackupRepository();
        $backup = MediaBackupMother::forPath('shared/file.png', '2222');

        $sut->save(MediaBackupMother::forPath('shared/other.png', '3333'));
        $sut->save($backup);

        $this->assertSame($backup, $sut->findByMediaId('2222'));
        $this->assertNull($sut->findByMediaId('4444'));
    }

    public function testFindExistingPaths(): void
    {
        $sut = new InMemoryMediaBackupRepository();

        $sut->save(MediaBackupMother::forPath('shared/a.png'));
        $sut->save(MediaBackupMother::forPath('shared/c.png'));

        $this->assertSame(['shared/a.png', 'shared/c.png'], $sut->findExistingPaths(['shared/a.png', 'shared/b.png', 'shared/c.png']));
        $this->assertSame([], $sut->findExistingPaths([]));
    }

    public function testFindRemovedBefore(): void
    {
        $sut = new InMemoryMediaBackupRepository();
        $limit = new \DateTimeImmutable('2026-11-01 04:00:00');

        $sut->save(MediaBackupMother::forPath('shared/active.png'));
        $sut->save(MediaBackupMother::removedAt('shared/later.png', new \DateTimeImmutable('2026-11-01 04:01:00')));
        $sut->save(MediaBackupMother::removedAt('shared/c.png', new \DateTimeImmutable('2026-10-01 10:00:00')));
        $sut->save(MediaBackupMother::removedAt('shared/b.png', new \DateTimeImmutable('2026-10-01 10:00:00')));
        $sut->save(MediaBackupMother::removedAt('shared/a.png', new \DateTimeImmutable('2026-11-01 04:00:00')));

        $paths = array_map(static fn(MediaBackup $backup): string => $backup->path(), $sut->findRemovedBefore($limit, 10));

        $this->assertSame(['shared/b.png', 'shared/c.png', 'shared/a.png'], $paths);
    }

    public function testFindRemovedBeforeRespectsTheMaximum(): void
    {
        $sut = new InMemoryMediaBackupRepository();

        $sut->save(MediaBackupMother::removedAt('shared/a.png', new \DateTimeImmutable('2026-10-01 10:00:00')));
        $sut->save(MediaBackupMother::removedAt('shared/b.png', new \DateTimeImmutable('2026-10-02 10:00:00')));

        $removed = $sut->findRemovedBefore(new \DateTimeImmutable('2026-11-01 04:00:00'), 1);

        $this->assertCount(1, $removed);
        $this->assertSame('shared/a.png', $removed[0]->path());
    }

    public function testFindRemovedBeforeWithAZeroMaximumRecoversOneBackup(): void
    {
        $sut = new InMemoryMediaBackupRepository();

        $sut->save(MediaBackupMother::removedAt('shared/a.png', new \DateTimeImmutable('2026-10-01 10:00:00')));
        $sut->save(MediaBackupMother::removedAt('shared/b.png', new \DateTimeImmutable('2026-10-02 10:00:00')));

        $removed = $sut->findRemovedBefore(new \DateTimeImmutable('2026-11-01 04:00:00'), 0);

        $this->assertCount(1, $removed);
        $this->assertSame('shared/a.png', $removed[0]->path());
    }
}
