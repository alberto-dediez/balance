<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterMaterialitySectorRequest implements Command
{
    public function __construct(
        public string $name = '',
    ) {}
}
