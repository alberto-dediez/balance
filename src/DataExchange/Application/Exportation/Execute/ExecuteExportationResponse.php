<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Execute;

use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\Shared\Application\CommandBus\CommandResponse;

class ExecuteExportationResponse implements CommandResponse
{
    public function __construct(
        public ExportationId $exportationId,
        public bool $success,
    ) {}
}
