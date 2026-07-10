<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterElectricityActivityRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public ?ElectricityMarketerId $electricityMarketerId,
        public ?GdOFactorId $gdOFactorId,
        public Quantity $quantity,
        public int $year,
        public EmissionFactorGroupId $emissionFactorGroupId,
        public ?string $source = null,
    ) {}
}
