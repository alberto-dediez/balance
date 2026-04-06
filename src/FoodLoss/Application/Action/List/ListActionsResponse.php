<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Action\List;

use App\FoodLoss\Domain\Model\Action\Action;

class ListActionsResponse
{
    /** @param array<Action> $actions */
    public function __construct(public array $actions = [], public int $totalResults = 0) {}
}
