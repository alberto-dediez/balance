<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess\Composting;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\Shared\Application\CommandBus\Command;

class AssessCompostingRequest implements Command
{
    public function __construct(
        public SurplusId $surplusId,
        public bool $canBeComposted,
        public ?ReasonId $reasonId,
        public ?WasteManagerId $wasteManagerId = null,
    ) {}
}
