<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Remove;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Shared\Application\CommandBus\Command;

class RemoveCriticalPointRequest implements Command
{
    public function __construct(public CriticalPointId $criticalPointId) {}
}
