<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause\Update;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Application\CommandBus\Command;

class UpdateSurplusCauseRequest implements Command
{
    public function __construct(
        public SurplusCauseId $surplusCauseId,
        public string $name,
    ) {}
}
