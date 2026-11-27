<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Backup;

use App\Library\Domain\Model\Backup\MediaBackup;

class MediaBackupMother
{
    public static function forPath(string $path, string $mediaId = '1111'): MediaBackup
    {
        return new MediaBackup($path, $mediaId, new \DateTimeImmutable('NOW'));
    }

    public static function removedAt(string $path, \DateTimeImmutable $at): MediaBackup
    {
        $backup = self::forPath($path);
        $backup->markRemoved($at);

        return $backup;
    }
}
