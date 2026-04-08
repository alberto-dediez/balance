<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Update;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class UpdateIncidenceRequest implements Command
{
    public function __construct(
        public IncidenceId $incidenceId,
        public string $name,
        public \DateTimeImmutable $exceptionOccurredOn,
        public IncidenceType $incidenceType,
        public CriticalPointId $originId,
        public string $description,
        public string $effect,
        public PropertyId $propertyId,
    ) {}
}
