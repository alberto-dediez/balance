<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\Register;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Application\CommandBus\Command;

class RegisterReasonRequest implements Command
{
    public function __construct(
        public string $description = '',
        public Priority $priority = Priority::Prevention,
    ) {}
}
