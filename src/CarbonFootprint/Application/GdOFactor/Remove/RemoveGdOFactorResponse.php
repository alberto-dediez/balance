<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Remove;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveGdOFactorResponse implements CommandResponse
{
    public function __construct(public GdOFactorId $gdOFactorId) {}
}
