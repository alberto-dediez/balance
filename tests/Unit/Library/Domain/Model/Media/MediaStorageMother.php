<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Infrastructure\Storage\InMemoryMediaStorage;

class MediaStorageMother
{
    public static function inMemory(): InMemoryMediaStorage
    {
        return new InMemoryMediaStorage();
    }

    /**
     * Creates a real local file so that the storage adapters have something to read.
     */
    public static function localFile(string $contents = 'a-file'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'media');

        if (false === $path) {
            throw new \RuntimeException('Unable to create a temporary file');
        }

        file_put_contents($path, $contents);

        return $path;
    }
}
