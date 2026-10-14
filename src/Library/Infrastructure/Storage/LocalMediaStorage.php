<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaPath;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class LocalMediaStorage implements MediaStorage
{
    public function __construct(private readonly string $basePath) {}

    public function store(MediaId $mediaId, ?AccountId $accountId, string $extension, string $localPath): string
    {
        $relativePath = MediaPath::from($mediaId, $accountId, $extension);

        $this->write($relativePath, $localPath);

        return $relativePath;
    }

    public function write(string $path, string $localPath): void
    {
        $absolutePath = $this->toAbsolutePath($path);

        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new NotStoredMediaException(sprintf('Unable to create media directory "%s"', $directory));
        }

        if (false === copy($localPath, $absolutePath)) {
            throw new NotStoredMediaException(sprintf('Unable to store media file at "%s"', $absolutePath));
        }
    }

    public function read(string $path): mixed
    {
        $stream = @fopen($this->toAbsolutePath($path), 'rb');

        if (false === $stream) {
            throw new NotStoredMediaException(sprintf('Unable to read media file "%s"', $path));
        }

        return $stream;
    }

    public function delete(string $path): void
    {
        if ('' === $path) {
            return;
        }

        $absolutePath = $this->toAbsolutePath($path);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    public function exists(string $path): bool
    {
        return '' !== $path && is_file($this->toAbsolutePath($path));
    }

    private function toAbsolutePath(string $relativePath): string
    {
        return rtrim($this->basePath, '/') . '/' . ltrim($relativePath, '/');
    }
}
