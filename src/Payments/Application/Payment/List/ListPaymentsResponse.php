<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\List;

use App\Payments\Domain\Model\Payment\Payment;

class ListPaymentsResponse
{
    /** @param array<Payment> $payments */
    public function __construct(public array $payments = [], public int $totalResults = 0) {}
}
