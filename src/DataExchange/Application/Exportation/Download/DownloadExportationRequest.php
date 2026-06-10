<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Download;

use App\DataExchange\Domain\Model\Exportation\ExportationId;

class DownloadExportationRequest
{
    public function __construct(
        public ExportationId $exportationId,
    ) {}
}
