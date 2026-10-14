<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaPath;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use PHPUnit\Framework\TestCase;

class InMemoryMediaStorageTest extends TestCase
{
    public function testStore(): void
    {
        $storage = MediaStorageMother::inMemory();

        $mediaId = MediaId::generate();
        $accountId = AccountId::generate();

        $path = $storage->store($mediaId, $accountId, 'png', MediaStorageMother::localFile('logo'));

        $this->assertSame(MediaPath::from($mediaId, $accountId, 'png'), $path);
        $this->assertTrue($storage->exists($path));
    }

    public function testWriteAndRead(): void
    {
        $storage = MediaStorageMother::inMemory();

        $storage->write('any/path/file.txt', MediaStorageMother::localFile('hola'));

        $stream = $storage->read('any/path/file.txt');

        $this->assertSame('hola', stream_get_contents($stream));

        fclose($stream);
    }

    public function testDelete(): void
    {
        $storage = MediaStorageMother::inMemoryWith(['shared/file.txt' => 'hola']);

        $this->assertTrue($storage->exists('shared/file.txt'));

        $storage->delete('shared/file.txt');

        $this->assertFalse($storage->exists('shared/file.txt'));
    }

    public function testReadUnknownPath(): void
    {
        $this->expectException(NotStoredMediaException::class);

        MediaStorageMother::inMemory()->read('shared/unknown.txt');
    }
}
