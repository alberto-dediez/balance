<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Exportation;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportWriter;

class SpyExportWriter implements ExportWriter
{
    /** @var array<int, array<int, scalar|null>> */
    public array $rows = [];

    public ?string $path = null;

    public ?ExportationFormat $format = null;

    public bool $closed = false;

    public function open(string $path, ExportationFormat $format): void
    {
        $this->path = $path;
        $this->format = $format;

        // Touch the file so the storage step finds content to persist.
        file_put_contents($path, '');
    }

    public function writeRow(array $cells): void
    {
        $this->rows[] = $cells;

        file_put_contents((string) $this->path, implode(',', array_map(static fn($cell): string => (string) $cell, $cells)) . "\n", FILE_APPEND);
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
