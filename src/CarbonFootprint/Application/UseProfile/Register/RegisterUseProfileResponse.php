<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\Register;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterUseProfileResponse implements CommandResponse
{
    public function __construct(
        public UseProfileId $useProfileId,
        public string $name,
    ) {}
}
