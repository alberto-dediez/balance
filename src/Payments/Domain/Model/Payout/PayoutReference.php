<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

final readonly class PayoutReference
{
    public function __construct(
        public string $gateway,
        public ?string $externalId = null,
    ) {}
}
