<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Storage;

use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryStorageSyncTaskRepository;
use App\Library\Infrastructure\Storage\FallbackMediaStorage;
use App\Library\Infrastructure\Storage\MediaStorageSelector;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use PHPUnit\Framework\TestCase;

class MediaStorageSelectorTest extends TestCase
{
    public function testSelectsTheFallbackStorageWhenEnabled(): void
    {
        $primary = MediaStorageMother::inMemory();
        $fallback = new FallbackMediaStorage($primary, MediaStorageMother::inMemory(), new InMemoryStorageSyncTaskRepository());

        $this->assertSame($fallback, (new MediaStorageSelector($primary, $fallback, true))->select());
    }

    public function testSelectsThePrimaryStorageWhenDisabled(): void
    {
        $primary = MediaStorageMother::inMemory();
        $fallback = new FallbackMediaStorage($primary, MediaStorageMother::inMemory(), new InMemoryStorageSyncTaskRepository());

        $this->assertSame($primary, (new MediaStorageSelector($primary, $fallback, false))->select());
    }
}
