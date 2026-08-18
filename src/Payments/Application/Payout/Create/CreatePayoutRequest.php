<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Create;

use App\Payments\Domain\Model\Payee\PayeeId;

final class CreatePayoutRequest
{
    public function __construct(
        public readonly PayeeId $payeeId,
        public readonly float $baseAmount,
        public readonly int $linesCount,
        public readonly string $currency = 'EUR',
        public readonly ?\DateTimeImmutable $periodFrom = null,
        public readonly ?\DateTimeImmutable $periodTo = null,
        public readonly ?string $notes = null,
    ) {}
}
