<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\AssignValues;

use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Shared\Application\CommandBus\CommandResponse;

class AssignValuesImportationResponse implements CommandResponse
{
    public function __construct(
        public ImportationId $importationId,
        public array $defaultValues,
    ) {}
}
