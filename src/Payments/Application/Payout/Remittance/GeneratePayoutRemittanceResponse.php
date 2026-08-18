<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Remittance;

final class GeneratePayoutRemittanceResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $filename,
        public readonly string $contentType,
        public readonly int $linesCount,
        public readonly float $totalAmount,
    ) {}
}
