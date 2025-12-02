<?php

namespace App\DataExchange\Application\Importation\AssignValues;

use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Shared\Application\CommandBus\Command;

class AssignValuesImportationRequest implements Command
{
    public function __construct(
        public ImportationId $importationId,
        public array $defaultValues
    ) {

    }
}
