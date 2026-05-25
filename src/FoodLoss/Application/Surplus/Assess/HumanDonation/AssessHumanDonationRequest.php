<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess\HumanDonation;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Shared\Application\CommandBus\Command;

class AssessHumanDonationRequest implements Command
{
    public function __construct(
        public SurplusId $surplusId,
        public bool $canBeHumanDonated,
        public ?ReasonId $reasonId,
    ) {}
}
