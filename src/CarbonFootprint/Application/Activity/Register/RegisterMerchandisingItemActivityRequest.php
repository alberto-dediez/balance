<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterMerchandisingItemActivityRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public MerchandisingItemId $merchandisingItemId,
        public ItemCondition $condition,
        public Quantity $quantity,
        public int $year,
        public EmissionFactorGroupId $emissionFactorGroupId,
    ) {}
}
