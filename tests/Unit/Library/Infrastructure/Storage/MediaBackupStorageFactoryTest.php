<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Storage;

use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Library\Infrastructure\Storage\MediaBackupStorageFactory;
use App\Library\Infrastructure\Storage\MediaStorageRegistry;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use PHPUnit\Framework\TestCase;

class MediaBackupStorageFactoryTest extends TestCase
{
    private InMemoryMediaStorage $local;

    private InMemoryMediaStorage $ovh;

    private InMemoryMediaStorage $googleDrive;

    private MediaStorageRegistry $registry;

    protected function setUp(): void
    {
        $this->local = MediaStorageMother::inMemory();
        $this->ovh = MediaStorageMother::inMemory();
        $this->googleDrive = MediaStorageMother::inMemory();
        $this->registry = new MediaStorageRegistry(['local' => $this->local, 'ovh' => $this->ovh, 'google_drive' => $this->googleDrive], 'local');
    }

    public function testReturnsTheLocalBackupWithAnOvhMainStorage(): void
    {
        $sut = $this->createSUT('ovh', 'local');

        $this->assertSame($this->local, $sut->create());
    }

    public function testReturnsTheOvhBackupWithALocalMainStorage(): void
    {
        $sut = $this->createSUT('local', 'ovh');

        $this->assertSame($this->ovh, $sut->create());
    }

    public function testSameDriverWithAnotherLocation(): void
    {
        $this->assertSame($this->local, $this->createSUT('local', 'local')->create());
        $this->assertSame($this->ovh, $this->createSUT('ovh', 'ovh')->create());
    }

    public function testSameDriverAndLocation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The backup storage must not use the same location as the main storage');

        $sut = $this->createSUT('ovh', 'ovh', backupLocations: ['local' => '/backup/library', 'ovh' => 'bucket/library']);

        $sut->create();
    }

    public function testSameLocationWithATrailingSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $sut = $this->createSUT('local', 'local', backupLocations: ['local' => '/app/var/uploads/library/', 'ovh' => 'bucket/library-backup']);

        $sut->create();
    }

    public function testLocalBackupInTheFallbackDirWithAnOvhMainStorage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The backup storage must not use the same location as the main storage');

        $sut = $this->createSUT('ovh', 'local', backupLocations: ['local' => '/app/var/uploads/library', 'ovh' => 'bucket/library-backup']);

        $sut->create();
    }

    public function testLocalBackupNestedInTheFallbackDir(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $sut = $this->createSUT('ovh', 'local', backupLocations: ['local' => '/app/var/uploads/library/backup', 'ovh' => 'bucket/library-backup']);

        $sut->create();
    }

    public function testFallbackDirNestedInTheLocalBackup(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $sut = $this->createSUT('local', 'local', backupLocations: ['local' => '/app/var', 'ovh' => 'bucket/library-backup']);

        $sut->create();
    }

    public function testLocalBackupInTheFallbackDirWrittenWithParentSegments(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $sut = $this->createSUT('ovh', 'local', backupLocations: ['local' => '/app/var/backups/../uploads/./library', 'ovh' => 'bucket/library-backup']);

        $sut->create();
    }

    public function testLocalBackupInTheFallbackDirResolvedThroughTheFilesystem(): void
    {
        $base = sys_get_temp_dir() . '/media-backup-factory-' . bin2hex(random_bytes(4));
        mkdir($base . '/uploads', 0o777, true);
        symlink($base . '/uploads', $base . '/link');

        try {
            $sut = new MediaBackupStorageFactory(
                $this->registry,
                'ovh',
                'local',
                ['local' => $base . '/uploads', 'ovh' => 'bucket/library'],
                ['local' => $base . '/link', 'ovh' => 'bucket/library-backup'],
                $base . '/uploads',
            );

            $this->expectException(\InvalidArgumentException::class);

            $sut->create();
        } finally {
            unlink($base . '/link');
            rmdir($base . '/uploads');
            rmdir($base);
        }
    }

    public function testLocalBackupNextToTheFallbackDirWithASharedPrefix(): void
    {
        $sut = $this->createSUT('ovh', 'local', backupLocations: ['local' => '/app/var/uploads/library-backup', 'ovh' => 'bucket/library-backup']);

        $this->assertSame($this->local, $sut->create());
    }

    public function testReturnsTheGoogleDriveBackupWithALocalMainStorage(): void
    {
        $sut = $this->createSUT('local', 'google_drive');

        $this->assertSame($this->googleDrive, $sut->create());
    }

    public function testGoogleDriveMainAndBackupWithAnotherFolder(): void
    {
        $sut = $this->createSUT('google_drive', 'google_drive');

        $this->assertSame($this->googleDrive, $sut->create());
    }

    public function testGoogleDriveMainAndBackupInTheSameFolder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The backup storage must not use the same location as the main storage');

        $sut = $this->createSUT('google_drive', 'google_drive', backupLocations: ['local' => '/backup/library', 'ovh' => 'bucket/library-backup', 'google_drive' => 'drive-folder']);

        $sut->create();
    }

    public function testGoogleDriveMainAndBackupWithoutFolders(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The backup storage must not use the same location as the main storage');

        $sut = $this->createSUT(
            'google_drive',
            'google_drive',
            primaryLocations: ['local' => '/app/var/uploads/library', 'ovh' => 'bucket/library', 'google_drive' => ''],
            backupLocations: ['local' => '/backup/library', 'ovh' => 'bucket/library-backup', 'google_drive' => ''],
        );

        $sut->create();
    }

    public function testUnknownBackupDriver(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown media storage "drive", expected one of: local, ovh, google_drive');

        $this->createSUT('local', 'drive')->create();
    }

    /**
     * @param array<string, string> $backupLocations
     * @param array<string, string> $primaryLocations
     */
    private function createSUT(
        string $primaryDriver,
        string $backupDriver,
        array $backupLocations = ['local' => '/backup/library', 'ovh' => 'bucket/library-backup', 'google_drive' => 'drive-backup-folder'],
        array $primaryLocations = ['local' => '/app/var/uploads/library', 'ovh' => 'bucket/library', 'google_drive' => 'drive-folder'],
    ): MediaBackupStorageFactory {
        return new MediaBackupStorageFactory(
            $this->registry,
            $primaryDriver,
            $backupDriver,
            $primaryLocations,
            $backupLocations,
            '/app/var/uploads/library',
        );
    }
}
