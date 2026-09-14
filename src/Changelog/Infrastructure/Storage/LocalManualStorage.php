<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Storage;

use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ManualStorage;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class LocalManualStorage implements ManualStorage
{
    public function __construct(private readonly string $basePath) {}

    public function exists(string $fileName): bool
    {
        if ($this->hasUnsafeFileName($fileName)) {
            return false;
        }

        return is_file($this->toAbsolutePath($fileName));
    }

    public function read(string $fileName): mixed
    {
        if (!$this->exists($fileName)) {
            throw new ManualNotFoundException($fileName);
        }

        $stream = @fopen($this->toAbsolutePath($fileName), 'rb');

        if (false === $stream) {
            throw new ManualNotFoundException($fileName);
        }

        return $stream;
    }

    public function readContents(string $fileName): string
    {
        if (!$this->exists($fileName)) {
            throw new ManualNotFoundException($fileName);
        }

        $contents = @file_get_contents($this->toAbsolutePath($fileName));

        if (false === $contents) {
            throw new ManualNotFoundException($fileName);
        }

        return $contents;
    }

    public function size(string $fileName): int
    {
        if (!$this->exists($fileName)) {
            throw new ManualNotFoundException($fileName);
        }

        $size = @filesize($this->toAbsolutePath($fileName));

        if (false === $size) {
            throw new ManualNotFoundException($fileName);
        }

        return $size;
    }

    private function hasUnsafeFileName(string $fileName): bool
    {
        return '' === $fileName || str_contains($fileName, '/') || str_contains($fileName, '\\') || str_contains($fileName, '..');
    }

    private function toAbsolutePath(string $fileName): string
    {
        return rtrim($this->basePath, '/') . '/' . $fileName;
    }
}
