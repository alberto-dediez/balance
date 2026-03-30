<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\Calculate;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class CalculateDailyWasteRequest implements Command
{
    public function __construct(
        public PropertyId $propertyId,
        public WasteId $wasteId,
        public WasteManagerId $wasteManagerId,
        public AccountId $accountId,
        public Purpose $purpose,
        public \DateTimeImmutable $day,
    ) {}
}
