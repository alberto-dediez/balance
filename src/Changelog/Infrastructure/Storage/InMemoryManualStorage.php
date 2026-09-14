<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Storage;

use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ManualStorage;

class InMemoryManualStorage implements ManualStorage
{
    /** @var array<string, string> */
    private array $files = [];

    public function add(string $fileName, string $contents): void
    {
        $this->files[$fileName] = $contents;
    }

    public function clear(): void
    {
        $this->files = [];
    }

    public function exists(string $fileName): bool
    {
        return array_key_exists($fileName, $this->files);
    }

    public function read(string $fileName): mixed
    {
        if (!$this->exists($fileName)) {
            throw new ManualNotFoundException($fileName);
        }

        $stream = fopen('php://memory', 'r+b');

        if (false === $stream) {
            throw new ManualNotFoundException($fileName);
        }

        fwrite($stream, $this->files[$fileName]);
        rewind($stream);

        return $stream;
    }

    public function readContents(string $fileName): string
    {
        if (!$this->exists($fileName)) {
            throw new ManualNotFoundException($fileName);
        }

        return $this->files[$fileName];
    }

    public function size(string $fileName): int
    {
        if (!$this->exists($fileName)) {
            throw new ManualNotFoundException($fileName);
        }

        return strlen($this->files[$fileName]);
    }
}
