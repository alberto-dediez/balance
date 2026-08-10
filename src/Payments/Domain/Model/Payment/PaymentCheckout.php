<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

final class PaymentCheckout
{
    /** @param array<string, string> $metadata */
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $customerEmail,
        public readonly string $successUrl,
        public readonly string $cancelUrl,
        public readonly array $metadata = [],
        public readonly ?\DateTimeImmutable $expiresAt = null,
    ) {}

    public function amountInCents(): int
    {
        return (int) round($this->amount * 100);
    }
}
