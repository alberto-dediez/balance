<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\Access;

class AccessProtocolResponse
{
    public function __construct(
        public string $path,
    ) {}
}
