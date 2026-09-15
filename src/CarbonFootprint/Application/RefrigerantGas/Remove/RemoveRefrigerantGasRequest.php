<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Remove;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\Shared\Application\CommandBus\Command;

class RemoveRefrigerantGasRequest implements Command
{
    public function __construct(public RefrigerantGasId $refrigerantGasId) {}
}
