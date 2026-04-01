<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Update;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateCriticalPointResponse implements CommandResponse
{
    public function __construct(
        public CriticalPointId $criticalPointId,
        public string $name,
    ) {}
}
