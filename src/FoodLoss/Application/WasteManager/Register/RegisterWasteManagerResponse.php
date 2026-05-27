<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\Register;

use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterWasteManagerResponse implements CommandResponse
{
    public function __construct(
        public WasteManagerId $wasteManagerId,
        public string $name,
    ) {}
}
