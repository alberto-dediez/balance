<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Remove;

use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveIncidenceResponse implements CommandResponse
{
    public function __construct(public IncidenceId $incidenceId) {}
}
