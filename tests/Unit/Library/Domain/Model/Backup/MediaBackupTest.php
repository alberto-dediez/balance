<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Backup;

use PHPUnit\Framework\TestCase;

class MediaBackupTest extends TestCase
{
    public function testNewBackup(): void
    {
        $backup = MediaBackupMother::forPath('shared/file.png', '2222');

        $this->assertSame('shared/file.png', $backup->path());
        $this->assertSame('2222', $backup->mediaId());
        $this->assertNull($backup->removedAt());
        $this->assertFalse($backup->isRemoved());
    }

    public function testMarkRemoved(): void
    {
        $backup = MediaBackupMother::forPath('shared/file.png');
        $at = new \DateTimeImmutable('2026-11-24 10:00:00');

        $backup->markRemoved($at);

        $this->assertSame($at, $backup->removedAt());
        $this->assertTrue($backup->isRemoved());
    }

    public function testMarkRemovedKeepsTheFirstDate(): void
    {
        $first = new \DateTimeImmutable('2026-11-24 10:00:00');
        $backup = MediaBackupMother::removedAt('shared/file.png', $first);

        $backup->markRemoved(new \DateTimeImmutable('2026-11-25 10:00:00'));

        $this->assertSame($first, $backup->removedAt());
    }
}
