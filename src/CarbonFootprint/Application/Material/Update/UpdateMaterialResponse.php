<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Update;

use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMaterialResponse implements CommandResponse
{
    public function __construct(
        public MaterialId $materialId,
        public string $name,
    ) {}
}
