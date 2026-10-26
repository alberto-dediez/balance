<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media;

use App\Library\Application\Media\MediaStorageFileCopier;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use PHPUnit\Framework\TestCase;

class MediaStorageFileCopierTest extends TestCase
{
    public function testCopy(): void
    {
        $source = MediaStorageMother::inMemoryWith(['shared/file.txt' => 'hola']);
        $target = MediaStorageMother::inMemory();

        (new MediaStorageFileCopier())->copy('shared/file.txt', $source, $target);

        $stream = $target->read('shared/file.txt');

        $this->assertSame('hola', stream_get_contents($stream));
        $this->assertTrue($source->exists('shared/file.txt'));

        fclose($stream);
    }

    public function testCopyAMissingFile(): void
    {
        $this->expectException(NotStoredMediaException::class);

        (new MediaStorageFileCopier())->copy('shared/file.txt', MediaStorageMother::inMemory(), MediaStorageMother::inMemory());
    }
}
