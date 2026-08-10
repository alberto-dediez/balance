<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

final class PaymentRedirect
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $redirectUrl,
    ) {}
}
