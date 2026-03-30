<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\ExecuteSingle;

use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Shared\Application\CommandBus\CommandResponse;

class ExecuteSingleResponse implements CommandResponse
{
    public function __construct(
        public ImportationId $importationId,
        public int $rowNumber,
        public bool $success,
    ) {}
}
