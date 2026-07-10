<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterWasteActivityRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public WasteId $wasteId,
        public Quantity $quantity,
        public int $year,
        public EmissionFactorGroupId $emissionFactorGroupId,
        public ?string $source = null,
        public ?Quantity $containerCapacity = null,
        public ?float $fillPercentage = null,
    ) {}
}
