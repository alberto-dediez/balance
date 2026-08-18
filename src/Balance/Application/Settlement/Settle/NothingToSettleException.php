<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Settle;

class NothingToSettleException extends \RuntimeException
{
    public function __construct(string $beneficiaryId)
    {
        parent::__construct(sprintf('No hay asientos pendientes de liquidar para %s.', $beneficiaryId), 409);
    }
}
