<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\SetDefault;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Application\CommandBus\CommandResponse;

class SetDefaultEmissionFactorGroupResponse implements CommandResponse
{
    public function __construct(
        public EmissionFactorGroupId $emissionFactorGroupId,
        public string $name,
    ) {}
}
