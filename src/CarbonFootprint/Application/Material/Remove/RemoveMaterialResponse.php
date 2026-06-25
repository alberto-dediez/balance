<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Remove;

use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveMaterialResponse implements CommandResponse
{
    public function __construct(public MaterialId $materialId) {}
}
