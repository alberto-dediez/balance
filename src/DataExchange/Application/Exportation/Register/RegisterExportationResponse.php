<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Register;

use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterExportationResponse implements CommandResponse
{
    public function __construct(
        public ExportationId $exportationId,
    ) {}
}
