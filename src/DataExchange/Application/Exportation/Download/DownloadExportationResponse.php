<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Download;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;

class DownloadExportationResponse
{
    /** @param resource $stream */
    public function __construct(
        public mixed $stream,
        public string $filename,
        public ExportationFormat $format,
    ) {}
}
