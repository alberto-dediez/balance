<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertCheckQueue\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterAlertCheckQueueRequest implements Command
{
    public function __construct(
        public readonly AccountId $accountId,
        public readonly \DateTimeImmutable $checkDate,
    ) {}
}
