<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Remit;

final class RemitPayoutsResponse
{
    public function __construct(public readonly int $remittedCount = 0) {}
}
