<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Remittance;

use App\Payments\Domain\Model\Payout\PayoutId;

final class GeneratePayoutRemittanceRequest
{
    /** @param list<PayoutId> $payoutIds */
    public function __construct(
        public readonly string $format = 'sepa',
        public readonly array $payoutIds = [],
        public readonly ?\DateTimeImmutable $executionDate = null,
    ) {}
}
