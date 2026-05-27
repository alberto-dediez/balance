<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\Update;

use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateWasteManagerResponse implements CommandResponse
{
    public function __construct(
        public WasteManagerId $wasteManagerId,
        public string $name,
    ) {}
}
