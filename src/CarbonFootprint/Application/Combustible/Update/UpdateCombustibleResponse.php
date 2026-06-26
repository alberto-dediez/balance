<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Update;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateCombustibleResponse implements CommandResponse
{
    public function __construct(
        public CombustibleId $combustibleId,
        public string $name,
    ) {}
}
