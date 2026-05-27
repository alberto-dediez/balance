<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\Remove;

use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\Shared\Application\CommandBus\Command;

class RemoveWasteManagerRequest implements Command
{
    public function __construct(public WasteManagerId $wasteManagerId) {}
}
