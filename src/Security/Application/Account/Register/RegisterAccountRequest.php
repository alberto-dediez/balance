<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Register;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Application\CommandBus\Command;

class RegisterAccountRequest implements Command
{
    public function __construct(
        public string $name = '',
        public bool $active = false,
        public ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ) {}
}
