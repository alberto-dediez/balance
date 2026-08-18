<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

final readonly class PayoutRemittanceLine
{
    public function __construct(
        public string $endToEndId,
        public float $amount,
        public string $currency,
        public string $creditorName,
        public string $iban,
        public ?string $bic,
        public string $concept,
    ) {}
}
