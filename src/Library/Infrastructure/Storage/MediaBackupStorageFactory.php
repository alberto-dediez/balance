<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaStorage;

final class MediaBackupStorageFactory
{
    private const string LOCAL_DRIVER = 'local';

    /**
     * @param array<string, string> $primaryLocations
     * @param array<string, string> $backupLocations
     */
    public function __construct(
        private readonly MediaStorageRegistry $registry,
        private readonly string $primaryDriver,
        private readonly string $backupDriver,
        private readonly array $primaryLocations,
        private readonly array $backupLocations,
        private readonly string $fallbackDir,
    ) {}

    public function create(): MediaStorage
    {
        if (
            ($this->primaryDriver === $this->backupDriver && $this->sameLocation($this->backupDriver))
            || $this->overlapsTheFallbackDir()
        ) {
            throw new \InvalidArgumentException('The backup storage must not use the same location as the main storage');
        }

        return $this->registry->get($this->backupDriver);
    }

    private function sameLocation(string $driver): bool
    {
        if (!isset($this->primaryLocations[$driver], $this->backupLocations[$driver])) {
            return false;
        }

        return rtrim($this->primaryLocations[$driver], '/') === rtrim($this->backupLocations[$driver], '/');
    }

    private function overlapsTheFallbackDir(): bool
    {
        if (self::LOCAL_DRIVER !== $this->backupDriver || !isset($this->backupLocations[self::LOCAL_DRIVER])) {
            return false;
        }

        $backupDir = self::normalize($this->backupLocations[self::LOCAL_DRIVER]);
        $fallbackDir = self::normalize($this->fallbackDir);

        return $backupDir === $fallbackDir
            || str_starts_with($backupDir, rtrim($fallbackDir, '/') . '/')
            || str_starts_with($fallbackDir, rtrim($backupDir, '/') . '/');
    }

    private static function normalize(string $path): string
    {
        $realPath = realpath($path);

        if (false !== $realPath) {
            return $realPath;
        }

        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }

            if ('..' === $segment) {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return (str_starts_with($path, '/') ? '/' : '') . implode('/', $segments);
    }
}
