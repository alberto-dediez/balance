<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Remove;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveRefrigerantGasResponse implements CommandResponse
{
    public function __construct(public RefrigerantGasId $refrigerantGasId) {}
}
