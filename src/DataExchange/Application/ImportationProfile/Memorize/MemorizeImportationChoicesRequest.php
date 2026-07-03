<?php

declare(strict_types=1);

namespace App\DataExchange\Application\ImportationProfile\Memorize;

use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Shared\Application\CommandBus\Command;

class MemorizeImportationChoicesRequest implements Command
{
    public function __construct(
        public ImportationId $importationId,
    ) {}
}
