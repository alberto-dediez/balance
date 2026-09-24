<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Infrastructure\Storage;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Changelog\Infrastructure\Storage\LocalAnnouncementManualStorage;
use App\Shared\Domain\Model\Locale\Locale;
use PHPUnit\Framework\TestCase;

class LocalAnnouncementManualStorageTest extends TestCase
{
    private string $basePath;

    private string $sourcePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/announcement-manuals-test-' . uniqid();
        $this->sourcePath = sys_get_temp_dir() . '/announcement-manual-source-' . uniqid() . '.pdf';
        file_put_contents($this->sourcePath, '%PDF-1.4 contents');
    }

    protected function tearDown(): void
    {
        @unlink($this->sourcePath);

        foreach (glob($this->basePath . '/*/*') ?: [] as $file) {
            @unlink($file);
        }

        foreach (glob($this->basePath . '/*') ?: [] as $directory) {
            @rmdir($directory);
        }

        @rmdir($this->basePath);
    }

    public function testStoreReturnsTheRelativePathAndTheFileExists(): void
    {
        $sut = new LocalAnnouncementManualStorage($this->basePath);
        $id = AnnouncementId::generate();

        $path = $sut->store($id, Locale::Spanish, $this->sourcePath);

        $this->assertSame(sprintf('%s/es.pdf', (string) $id), $path);
        $this->assertTrue($sut->exists($path));
        $this->assertFileExists($this->basePath . '/' . $path);
    }

    public function testReadAndSize(): void
    {
        $sut = new LocalAnnouncementManualStorage($this->basePath);

        $path = $sut->store(AnnouncementId::generate(), Locale::English, $this->sourcePath);

        $stream = $sut->read($path);

        $this->assertSame('%PDF-1.4 contents', stream_get_contents($stream));
        fclose($stream);

        $this->assertSame(strlen('%PDF-1.4 contents'), $sut->size($path));
    }

    public function testDelete(): void
    {
        $sut = new LocalAnnouncementManualStorage($this->basePath);

        $path = $sut->store(AnnouncementId::generate(), Locale::Spanish, $this->sourcePath);

        $sut->delete($path);

        $this->assertFalse($sut->exists($path));
    }

    public function testPathsWithParentDirectoriesAreRejected(): void
    {
        $sut = new LocalAnnouncementManualStorage($this->basePath);

        $this->assertFalse($sut->exists('../etc/passwd'));

        $this->expectException(ManualNotFoundException::class);

        $sut->read('../etc/passwd');
    }
}
