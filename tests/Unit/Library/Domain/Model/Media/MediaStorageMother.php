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
     * @param array<string,string> $files
     */
    public static function inMemoryWith(array $files): InMemoryMediaStorage
    {
        $storage = self::inMemory();

        foreach ($files as $path => $contents) {
            $storage->write($path, self::localFile($contents));
        }

        return $storage;
    }

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
