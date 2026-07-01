<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\Remove;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Application\CommandBus\Command;

class RemoveEmissionFactorGroupRequest implements Command
{
    public function __construct(public EmissionFactorGroupId $emissionFactorGroupId) {}
}
