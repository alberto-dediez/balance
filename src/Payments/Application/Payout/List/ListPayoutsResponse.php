<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\List;

use App\Payments\Domain\Model\Payout\Payout;

class ListPayoutsResponse
{
    /** @param array<Payout> $payouts */
    public function __construct(public array $payouts = [], public int $totalResults = 0) {}
}
