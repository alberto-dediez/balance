<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

interface ExportWriter
{
    public function open(string $path, ExportationFormat $format): void;

    /** @param array<int, scalar|null> $cells */
    public function writeRow(array $cells): void;

    public function close(): void;
}
