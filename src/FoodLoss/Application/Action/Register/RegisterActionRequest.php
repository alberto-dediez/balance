<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Action\Register;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Application\CommandBus\Command;

class RegisterActionRequest implements Command
{
    public function __construct(
        public string $description = '',
        public Priority $priority = Priority::Prevention,
    ) {}
}
