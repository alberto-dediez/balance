<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\Update;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateReasonResponse implements CommandResponse
{
    public function __construct(
        public ReasonId $reasonId,
        public string $description,
    ) {}
}
