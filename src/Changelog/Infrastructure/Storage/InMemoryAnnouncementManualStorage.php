<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Storage;

use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementManualStorage;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAnnouncementManualStorage implements AnnouncementManualStorage
{
    /** @var array<string, string> */
    private array $files = [];

    public function add(string $path, string $contents): void
    {
        $this->files[$path] = $contents;
    }

    public function clear(): void
    {
        $this->files = [];
    }

    public function store(AnnouncementId $id, Locale $locale, string $localPath): string
    {
        $path = sprintf('%s/%s.pdf', (string) $id, $locale->value);

        $contents = @file_get_contents($localPath);

        $this->files[$path] = false !== $contents ? $contents : '';

        return $path;
    }

    public function read(string $path): mixed
    {
        if (!$this->exists($path)) {
            throw new ManualNotFoundException($path);
        }

        $stream = fopen('php://memory', 'r+b');

        if (false === $stream) {
            throw new ManualNotFoundException($path);
        }

        fwrite($stream, $this->files[$path]);
        rewind($stream);

        return $stream;
    }

    public function exists(string $path): bool
    {
        return array_key_exists($path, $this->files);
    }

    public function size(string $path): int
    {
        if (!$this->exists($path)) {
            throw new ManualNotFoundException($path);
        }

        return strlen($this->files[$path]);
    }

    public function delete(string $path): void
    {
        unset($this->files[$path]);
    }
}
