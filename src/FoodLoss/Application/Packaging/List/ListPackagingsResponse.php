<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\List;

class ListPackagingsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\Packaging\Packaging> $packagings */
    public function __construct(public array $packagings = [], public int $totalResults = 0) {}
}
