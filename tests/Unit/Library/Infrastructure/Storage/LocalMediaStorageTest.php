<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Infrastructure\Storage\LocalMediaStorage;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use PHPUnit\Framework\TestCase;

class LocalMediaStorageTest extends TestCase
{
    private string $baseDir;

    private LocalMediaStorage $storage;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/library_storage_test_' . uniqid();
        $this->storage = new LocalMediaStorage($this->baseDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->baseDir);
    }

    public function testStoreCreatesTheDirectoriesAndReturnsTheMediaPath(): void
    {
        $mediaId = MediaId::generate();
        $accountId = AccountId::generate();

        $path = $this->storage->store($mediaId, $accountId, 'png', MediaStorageMother::localFile('logo'));

        $this->assertSame(sprintf('%s/%s.png', $accountId, $mediaId), $path);
        $this->assertFileExists($this->baseDir . '/' . $path);
    }

    public function testStoreWithoutAccountUsesTheSharedScope(): void
    {
        $mediaId = MediaId::generate();

        $path = $this->storage->store($mediaId, null, 'png', MediaStorageMother::localFile('logo'));

        $this->assertSame(sprintf('shared/%s.png', $mediaId), $path);
        $this->assertTrue($this->storage->exists($path));
    }

    public function testWriteAndRead(): void
    {
        $this->storage->write('shared/file.txt', MediaStorageMother::localFile('hola'));

        $stream = $this->storage->read('shared/file.txt');

        $this->assertSame('hola', stream_get_contents($stream));

        fclose($stream);
    }

    public function testWriteOverwritesAnExistingFile(): void
    {
        $this->storage->write('shared/file.txt', MediaStorageMother::localFile('hola'));
        $this->storage->write('shared/file.txt', MediaStorageMother::localFile('adios'));

        $stream = $this->storage->read('shared/file.txt');

        $this->assertSame('adios', stream_get_contents($stream));

        fclose($stream);
    }

    public function testWriteWhenTheDirectoryCannotBeCreated(): void
    {
        mkdir($this->baseDir);
        touch($this->baseDir . '/shared');

        $this->expectException(NotStoredMediaException::class);
        $this->expectExceptionMessage(sprintf('Unable to create media directory "%s/shared"', $this->baseDir));

        $this->storage->write('shared/file.txt', MediaStorageMother::localFile('hola'));
    }

    public function testWriteAMissingLocalFile(): void
    {
        $this->expectException(NotStoredMediaException::class);
        $this->expectExceptionMessage(sprintf('Unable to store media file at "%s/shared/file.txt"', $this->baseDir));

        $this->storage->write('shared/file.txt', $this->baseDir . '/missing.txt');
    }

    public function testReadAMissingFile(): void
    {
        $this->expectException(NotStoredMediaException::class);
        $this->expectExceptionMessage('Unable to read media file "shared/file.txt"');

        $this->storage->read('shared/file.txt');
    }

    public function testExists(): void
    {
        $this->storage->write('shared/file.txt', MediaStorageMother::localFile('hola'));

        $this->assertTrue($this->storage->exists('shared/file.txt'));
        $this->assertFalse($this->storage->exists('shared/other.txt'));
        $this->assertFalse($this->storage->exists(''));
    }

    public function testDelete(): void
    {
        $this->storage->write('shared/file.txt', MediaStorageMother::localFile('hola'));

        $this->storage->delete('shared/file.txt');

        $this->assertFalse($this->storage->exists('shared/file.txt'));
    }

    public function testDeleteAMissingFileDoesNothing(): void
    {
        $this->storage->delete('shared/file.txt');
        $this->storage->delete('');

        $this->assertFalse($this->storage->exists('shared/file.txt'));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
