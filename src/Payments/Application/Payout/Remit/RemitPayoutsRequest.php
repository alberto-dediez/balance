<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Remit;

use App\Payments\Domain\Model\Payout\PayoutId;

final class RemitPayoutsRequest
{
    /** @param list<PayoutId> $payoutIds */
    public function __construct(
        public readonly array $payoutIds = [],
        public readonly string $format = 'sepa',
        public readonly ?string $reference = null,
    ) {}
}
