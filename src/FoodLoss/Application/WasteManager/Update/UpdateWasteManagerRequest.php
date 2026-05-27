<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\Update;

use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\Shared\Application\CommandBus\Command;

class UpdateWasteManagerRequest implements Command
{
    public function __construct(
        public WasteManagerId $wasteManagerId,
        public string $name,
        public bool $animalFeedAuthorized,
        public bool $compostAuthorized,
    ) {}
}
