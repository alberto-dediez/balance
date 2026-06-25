<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Remove;

use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\Shared\Application\CommandBus\Command;

class RemoveMaterialRequest implements Command
{
    public function __construct(public MaterialId $materialId) {}
}
