<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\RegisterForAccount;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterAccountTripCalculationLinkRequest implements Command
{
    public function __construct(
        public ?AccountId $accountId = null,
    ) {}
}
