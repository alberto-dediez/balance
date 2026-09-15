<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Update;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\Shared\Application\CommandBus\Command;

class UpdateRefrigerantGasRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public RefrigerantGasId $refrigerantGasId,
        public string $name,
        public array $emissionFactors = [],
    ) {}
}
