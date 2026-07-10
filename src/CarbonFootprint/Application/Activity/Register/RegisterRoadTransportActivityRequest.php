<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterRoadTransportActivityRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public RoadTransportId $roadTransportId,
        public Quantity $quantity,
        public int $year,
        public EmissionFactorGroupId $emissionFactorGroupId,
        public ?string $source = null,
    ) {}
}
