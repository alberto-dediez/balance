<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Alerts;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\TimedCommand;

final class CheckCreditAlertsRequest implements TimedCommand
{
    public function __construct(
        public readonly ?AccountId $accountId = null,
        public readonly ?ProjectId $projectId = null,
        public readonly \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($this->accountId, $this->projectId, $now);
    }
}
