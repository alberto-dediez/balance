<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Alerts;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

final class CheckCreditAlertsRequest implements Command
{
    public function __construct(
        public readonly ?AccountId $accountId = null,
        public readonly ?ProjectId $projectId = null,
        public readonly \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
