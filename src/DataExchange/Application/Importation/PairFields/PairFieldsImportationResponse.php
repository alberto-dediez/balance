<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\PairFields;

use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Shared\Application\CommandBus\CommandResponse;

class PairFieldsImportationResponse implements CommandResponse
{
    public function __construct(
        public ImportationId $importationId,
        /** @var ImportationColumnConfiguration[] $configurations */
        public array $configurations,
    ) {}
}
