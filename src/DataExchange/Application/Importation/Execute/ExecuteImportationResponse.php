<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\Execute;

use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Shared\Application\CommandBus\CommandResponse;

class ExecuteImportationResponse implements CommandResponse
{
    public function __construct(
        public ImportationId $importationId,
    ) {}
}
