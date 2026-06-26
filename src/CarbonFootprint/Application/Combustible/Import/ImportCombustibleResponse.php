<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Import;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportCombustibleResponse implements CommandResponse
{
    public function __construct(
        public CombustibleId $combustibleId,
    ) {}
}
