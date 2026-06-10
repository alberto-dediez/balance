<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Storage;

use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\DataExchange\Domain\Model\Exportation\ExportStorage;
use App\DataExchange\Domain\Model\Exportation\NotExportedException;
use App\DataExchange\Domain\Model\Exportation\StoredFile;
use App\Security\Domain\Model\Account\AccountId;

class FilesystemExportStorage implements ExportStorage
{
    public function __construct(private readonly string $basePath) {}

    public function store(ExportationId $id, ?AccountId $accountId, string $extension, string $localPath): StoredFile
    {
        $scope = $accountId instanceof AccountId ? (string) $accountId : 'shared';

        $relativePath = sprintf('%s/%s.%s', $scope, (string) $id, $extension);

        $absolutePath = $this->toAbsolutePath($relativePath);

        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new NotExportedException(sprintf('Unable to create export directory "%s"', $directory));
        }

        if (false === copy($localPath, $absolutePath)) {
            throw new NotExportedException(sprintf('Unable to store export file at "%s"', $absolutePath));
        }

        return new StoredFile($relativePath, $extension);
    }

    public function read(StoredFile $file): mixed
    {
        $stream = @fopen($this->toAbsolutePath((string) $file->path()), 'rb');

        if (false === $stream) {
            throw new NotExportedException(sprintf('Unable to read export file "%s"', (string) $file->path()));
        }

        return $stream;
    }

    public function delete(StoredFile $file): void
    {
        if ($file->isEmpty()) {
            return;
        }

        $absolutePath = $this->toAbsolutePath((string) $file->path());

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    public function exists(StoredFile $file): bool
    {
        return !$file->isEmpty() && is_file($this->toAbsolutePath((string) $file->path()));
    }

    private function toAbsolutePath(string $relativePath): string
    {
        return rtrim($this->basePath, '/') . '/' . ltrim($relativePath, '/');
    }
}
