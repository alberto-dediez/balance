<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Start;

final class StartPaymentRequest
{
    /** @param array<string, string> $metadata */
    public function __construct(
        public readonly string $subjectType,
        public readonly string $subjectId,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $customerEmail,
        public readonly string $customerName,
        public readonly string $successUrl,
        public readonly string $cancelUrl,
        public readonly string $currency = 'EUR',
        public readonly array $metadata = [],
        public readonly ?\DateTimeImmutable $expiresAt = null,
    ) {}
}
