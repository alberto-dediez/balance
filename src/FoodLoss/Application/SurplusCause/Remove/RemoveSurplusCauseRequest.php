<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause\Remove;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Application\CommandBus\Command;

class RemoveSurplusCauseRequest implements Command
{
    public function __construct(public SurplusCauseId $surplusCauseId) {}
}
