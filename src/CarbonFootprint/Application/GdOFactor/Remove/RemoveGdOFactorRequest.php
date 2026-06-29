<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Remove;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\Shared\Application\CommandBus\Command;

class RemoveGdOFactorRequest implements Command
{
    public function __construct(public GdOFactorId $gdOFactorId) {}
}
