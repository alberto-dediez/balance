<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Register;

use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterIncidenceRequest implements Command
{
    public function __construct(
        public string $name,
        public \DateTimeImmutable $exceptionOccurredOn,
        public IncidenceType $incidenceType,
        public string $description,
        public string $effect,
        public PropertyId $propertyId,
        public AccountId $accountId,
    ) {}
}
