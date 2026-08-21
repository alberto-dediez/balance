<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Alerts;

use App\Shared\Application\CommandBus\CommandResponse;

final class CheckCreditAlertsResponse implements CommandResponse
{
    public function __construct(
        public readonly int $evaluated = 0,
        public readonly int $alerted = 0,
    ) {}
}
