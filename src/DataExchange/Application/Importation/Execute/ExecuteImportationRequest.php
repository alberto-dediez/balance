<?php

namespace App\DataExchange\Application\Importation\Execute;

use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Shared\Application\CommandBus\Command;

class ExecuteImportationRequest implements Command
{
    public function __construct(
        public ImportationId $importationId
    ) {

    }
}
