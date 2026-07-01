<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Register;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterEventCarbonFootprintRequest implements Command
{
    public function __construct(
        public EventId $eventId,
        public AccountId $accountId,
        public EmissionFactorGroupId $emissionFactorGroupId,
        public ?\DateTimeImmutable $calculationPeriodStartDate = null,
        public ?\DateTimeImmutable $calculationPeriodEndDate = null,
    ) {}
}
