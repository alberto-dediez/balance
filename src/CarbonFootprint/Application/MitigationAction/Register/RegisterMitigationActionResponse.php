<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Register;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterMitigationActionResponse implements CommandResponse
{
    public function __construct(
        public MitigationActionId $mitigationActionId,
        public string $name,
    ) {}
}
