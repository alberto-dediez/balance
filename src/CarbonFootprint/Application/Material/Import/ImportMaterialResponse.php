<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Import;

use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportMaterialResponse implements CommandResponse
{
    public function __construct(
        public MaterialId $materialId,
    ) {}
}
