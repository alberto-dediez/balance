<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Register;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterGdOFactorResponse implements CommandResponse
{
    public function __construct(
        public GdOFactorId $gdOFactorId,
        public string $name,
    ) {}
}
