<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterMobilityActivityRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public MobilityId $mobilityId,
        public Quantity $quantity,
        public int $year,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
