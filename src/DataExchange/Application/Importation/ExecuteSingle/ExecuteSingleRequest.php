<?php

namespace App\DataExchange\Application\Importation\ExecuteSingle;

use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Shared\Application\CommandBus\Command;

class ExecuteSingleRequest implements Command
{
    public function __construct(
        public ImportationId $importationId,
        public int $rowNumber,
        public Command $command,
    ) {

    }
}
