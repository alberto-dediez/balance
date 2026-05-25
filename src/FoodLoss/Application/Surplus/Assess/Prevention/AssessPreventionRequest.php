<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess\Prevention;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Shared\Application\CommandBus\Command;

class AssessPreventionRequest implements Command
{
    public function __construct(
        public SurplusId $surplusId,
        public bool $canBePrevented,
        public ?ActionId $actionId,
    ) {}
}
