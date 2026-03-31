<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ServiceCategory\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterServiceCategoryRequest implements Command
{
    public function __construct(
        public string $name = '',
    ) {}
}
