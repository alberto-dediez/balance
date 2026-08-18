<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

final readonly class PayoutInstruction
{
    /** @param array<string, string> $metadata */
    public function __construct(
        public PayoutId $payoutId,
        public string $number,
        public float $amount,
        public string $currency,
        public string $concept,
        public string $holderName,
        public ?string $iban = null,
        public ?string $bic = null,
        public ?string $externalId = null,
        public array $metadata = [],
    ) {}
}
