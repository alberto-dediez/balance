<?php

namespace App\DataExchange\Application\Importation\PairFields;

use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Shared\Application\CommandBus\Command;

class PairFieldsImportationRequest implements Command
{
    public function __construct(
        public ImportationId $importationId,
        /** @var ImportationColumnConfiguration[] $configurations */
        public array $configurations
    ) {

    }
}
