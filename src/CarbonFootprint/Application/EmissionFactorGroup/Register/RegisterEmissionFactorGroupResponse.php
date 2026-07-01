<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\Register;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterEmissionFactorGroupResponse implements CommandResponse
{
    public function __construct(
        public EmissionFactorGroupId $emissionFactorGroupId,
        public string $code,
        public string $name,
    ) {}
}
