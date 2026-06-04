<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class CheckAlertsRequest implements Command
{
    public function __construct(
        public readonly \DateTimeImmutable $checkDate,
        public readonly AccountId $accountId,
    ) {}
}
