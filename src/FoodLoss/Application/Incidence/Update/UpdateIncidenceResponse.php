<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Update;

use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateIncidenceResponse implements CommandResponse
{
    public function __construct(
        public IncidenceId $incidenceId,
        public string $name,
    ) {}
}
