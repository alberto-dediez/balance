<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\List;

use App\Payments\Domain\Model\Payee\Payee;

class ListPayeesResponse
{
    /** @param array<Payee> $payees */
    public function __construct(public array $payees = [], public int $totalResults = 0) {}
}
