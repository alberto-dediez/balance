<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Storage;

use App\Library\Infrastructure\Storage\MediaStorageRegistry;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use PHPUnit\Framework\TestCase;

class MediaStorageRegistryTest extends TestCase
{
    public function testGet(): void
    {
        $local = MediaStorageMother::inMemory();
        $ovh = MediaStorageMother::inMemory();

        $sut = new MediaStorageRegistry(['local' => $local, 'ovh' => $ovh], 'local');

        $this->assertSame($local, $sut->get('local'));
        $this->assertSame($ovh, $sut->get('ovh'));
        $this->assertSame(['local', 'ovh'], $sut->drivers());
    }

    public function testDefault(): void
    {
        $local = MediaStorageMother::inMemory();
        $ovh = MediaStorageMother::inMemory();

        $sut = new MediaStorageRegistry(['local' => $local, 'ovh' => $ovh], 'ovh');

        $this->assertSame($ovh, $sut->default());
    }

    public function testGetUnknownDriver(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown media storage "s3", expected one of: local, ovh');

        $sut = new MediaStorageRegistry(['local' => MediaStorageMother::inMemory(), 'ovh' => MediaStorageMother::inMemory()], 'local');

        $sut->get('s3');
    }

    public function testUnknownDefaultDriver(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown media storage "s3", expected one of: local, ovh');

        new MediaStorageRegistry(['local' => MediaStorageMother::inMemory(), 'ovh' => MediaStorageMother::inMemory()], 's3');
    }
}
