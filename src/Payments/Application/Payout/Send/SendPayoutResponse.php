<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Send;

use App\Payments\Domain\Model\Payout\PayoutStatus;

final class SendPayoutResponse
{
    public function __construct(
        public readonly PayoutStatus $status,
        public readonly ?string $externalId = null,
    ) {}
}
