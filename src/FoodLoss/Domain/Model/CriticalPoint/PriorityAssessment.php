<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonId;

class PriorityAssessment
{
    /**
     * @param ActionId[] $actionIds
     * @param ReasonId[] $reasonIds
     */
    public function __construct(
        public Priority $priority = Priority::Prevention,
        public bool $isPossible = false,
        public array $actionIds = [],
        public array $reasonIds = [],
    ) {}
}
