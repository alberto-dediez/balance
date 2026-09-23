<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Storage;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementManualStorage;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class LocalAnnouncementManualStorage implements AnnouncementManualStorage
{
    public function __construct(private readonly string $basePath) {}

    public function store(AnnouncementId $id, Locale $locale, string $localPath): string
    {
        $relativePath = sprintf('%s/%s.pdf', (string) $id, $locale->value);

        $absolutePath = $this->toAbsolutePath($relativePath);

        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create announcement manual directory "%s"', $directory));
        }

        if (false === @copy($localPath, $absolutePath)) {
            throw new \RuntimeException(sprintf('Unable to store announcement manual at "%s"', $absolutePath));
        }

        return $relativePath;
    }

    public function read(string $path): mixed
    {
        if (!$this->exists($path)) {
            throw new ManualNotFoundException($path);
        }

        $stream = @fopen($this->toAbsolutePath($path), 'rb');

        if (false === $stream) {
            throw new ManualNotFoundException($path);
        }

        return $stream;
    }

    public function exists(string $path): bool
    {
        if ($this->hasUnsafePath($path)) {
            return false;
        }

        return is_file($this->toAbsolutePath($path));
    }

    public function size(string $path): int
    {
        if (!$this->exists($path)) {
            throw new ManualNotFoundException($path);
        }

        $size = @filesize($this->toAbsolutePath($path));

        if (false === $size) {
            throw new ManualNotFoundException($path);
        }

        return $size;
    }

    public function delete(string $path): void
    {
        if ($this->hasUnsafePath($path)) {
            throw new ManualNotFoundException($path);
        }

        $absolutePath = $this->toAbsolutePath($path);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function hasUnsafePath(string $path): bool
    {
        return '' === $path || str_starts_with($path, '/') || str_contains($path, '\\') || str_contains($path, '..');
    }

    private function toAbsolutePath(string $relativePath): string
    {
        return rtrim($this->basePath, '/') . '/' . $relativePath;
    }
}
