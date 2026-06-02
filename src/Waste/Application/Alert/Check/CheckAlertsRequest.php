<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Check;

use App\Security\Domain\Model\Account\AccountId;

class CheckAlertsRequest
{
    public function __construct(
        public readonly ?\DateTimeImmutable $checkDate = null,
        public readonly ?AccountId $accountId = null,
    ) {}
}
