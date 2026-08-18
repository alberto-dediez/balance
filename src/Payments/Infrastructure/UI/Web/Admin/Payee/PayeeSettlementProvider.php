<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payee;

use App\Payments\Domain\Model\Payee\Payee;

interface PayeeSettlementProvider
{
    /**
     * @param array<Payee> $payees
     *
     * @return array<string, PayeeSettlement> keyed by payee id
     */
    public function settlementsOf(array $payees): array;
}
