<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Event\Register;

use App\EventOperations\Domain\Model\Event\EventSector;
use App\EventOperations\Domain\Model\Event\EventSize;
use App\EventOperations\Domain\Model\Event\EventTypology;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterEventRequest implements Command
{
    public function __construct(
        public string $name = '',
        public ?DestinationId $location = null,
        public ?PropertyId $propertyId = null,
        public ?CompanyId $companyId = null,
        public ?EventSize $size = null,
        public ?int $estimatedAttendees = null,
        public ?EventSector $sector = null,
        public ?EventTypology $typology = null,
        public ?string $venuePlaceReference = null,
        public ?\DateTimeImmutable $assemblyStartDate = null,
        public ?\DateTimeImmutable $assemblyEndDate = null,
        public ?\DateTimeImmutable $celebrationStartDate = null,
        public ?\DateTimeImmutable $celebrationEndDate = null,
        public ?\DateTimeImmutable $breakdownStartDate = null,
        public ?\DateTimeImmutable $breakdownEndDate = null,
        public ?AccountId $accountId = null,
    ) {}
}
