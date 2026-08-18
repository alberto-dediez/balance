<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payee;

final readonly class PayeeSettlement
{
    public function __construct(
        public float $pendingAmount = 0.0,
        public ?string $settlePath = null,
    ) {}

    public function isSettleable(): bool
    {
        return null !== $this->settlePath;
    }
}
