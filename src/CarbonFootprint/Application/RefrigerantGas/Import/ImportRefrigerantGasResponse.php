<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Import;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportRefrigerantGasResponse implements CommandResponse
{
    public function __construct(
        public RefrigerantGasId $refrigerantGasId,
    ) {}
}
