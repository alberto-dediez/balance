<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Import;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportGdOFactorResponse implements CommandResponse
{
    public function __construct(
        public GdOFactorId $gdOFactorId,
    ) {}
}
