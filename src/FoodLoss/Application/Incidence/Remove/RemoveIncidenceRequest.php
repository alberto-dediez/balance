<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Remove;

use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\Shared\Application\CommandBus\Command;

class RemoveIncidenceRequest implements Command
{
    public function __construct(public IncidenceId $incidenceId) {}
}
