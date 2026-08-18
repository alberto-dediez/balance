<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

final readonly class PayoutStatementLine
{
    public function __construct(
        public \DateTimeImmutable $occurredAt,
        public string $concept,
        public ?string $projectName,
        public ?string $purchaseReference,
        public ?float $tonnes,
        public float $amount,
    ) {}
}
