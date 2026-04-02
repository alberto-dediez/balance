<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\Register;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterReasonResponse implements CommandResponse
{
    public function __construct(
        public ReasonId $reasonId,
        public string $description,
    ) {}
}
