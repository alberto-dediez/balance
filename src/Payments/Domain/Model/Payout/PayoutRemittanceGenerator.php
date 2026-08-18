<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

interface PayoutRemittanceGenerator
{
    public function format(): string;

    public function extension(): string;

    public function contentType(): string;

    /** @param list<PayoutRemittanceLine> $lines */
    public function generate(array $lines, \DateTimeImmutable $executionDate): string;
}
