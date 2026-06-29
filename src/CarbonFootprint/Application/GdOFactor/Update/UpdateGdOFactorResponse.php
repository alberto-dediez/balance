<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Update;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateGdOFactorResponse implements CommandResponse
{
    public function __construct(
        public GdOFactorId $gdOFactorId,
        public string $name,
    ) {}
}
