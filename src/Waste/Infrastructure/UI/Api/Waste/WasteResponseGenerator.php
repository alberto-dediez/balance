<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\Waste;

use App\Waste\Domain\Model\Waste\Waste as ModelWaste;
use App\Waste\Infrastructure\UI\Api\Waste\WasteResponse as ApiWaste;

class WasteResponseGenerator
{
    public function generate(ModelWaste $model): ApiWaste
    {
        return new ApiWaste(
            wasteId: (string) $model->id(),
            name: $model->name(),
            isDangerous: $model->isDangerous(),
            density: $model->density(),
            color: $model->color(),
        );
    }
}
