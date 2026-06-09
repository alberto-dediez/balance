<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Exportation;

use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\DataExchange\Domain\Model\Exportation\ExportStorage;
use App\DataExchange\Domain\Model\Exportation\StoredFile;
use App\Security\Domain\Model\Account\AccountId;

class SpyExportStorage implements ExportStorage
{
    public int $storeCalls = 0;

    public int $deleteCalls = 0;

    /** @var array<string, string> */
    private array $contents = [];

    public function store(ExportationId $id, ?AccountId $accountId, string $extension, string $localPath): StoredFile
    {
        ++$this->storeCalls;

        $path = sprintf('stored/%s.%s', (string) $id, $extension);

        $this->contents[$path] = (string) file_get_contents($localPath);

        return new StoredFile($path, $extension);
    }

    public function read(StoredFile $file): mixed
    {
        $stream = fopen('php://memory', 'rb+');

        if (false === $stream) {
            throw new \RuntimeException('Unable to open memory stream');
        }

        fwrite($stream, $this->contents[(string) $file->path()] ?? '');
        rewind($stream);

        return $stream;
    }

    public function delete(StoredFile $file): void
    {
        ++$this->deleteCalls;

        unset($this->contents[(string) $file->path()]);
    }

    public function exists(StoredFile $file): bool
    {
        return isset($this->contents[(string) $file->path()]);
    }
}
