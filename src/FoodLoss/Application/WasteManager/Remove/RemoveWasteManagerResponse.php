<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\Remove;

use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveWasteManagerResponse implements CommandResponse
{
    public function __construct(public WasteManagerId $wasteManagerId) {}
}
