<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Action\List;

class ListActionsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\Action\Action> $actions */
    public function __construct(public array $actions = [], public int $totalResults = 0) {}
}
