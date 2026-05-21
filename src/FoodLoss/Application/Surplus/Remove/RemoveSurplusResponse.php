<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Remove;

use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveSurplusResponse implements CommandResponse
{
    public function __construct(public SurplusId $surplusId) {}
}
