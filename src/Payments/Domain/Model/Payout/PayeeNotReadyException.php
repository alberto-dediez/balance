<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Payments\Domain\Model\Payee\PayeeId;

class PayeeNotReadyException extends \RuntimeException
{
    public function __construct(PayeeId $id)
    {
        parent::__construct(
            sprintf('El beneficiario %s no tiene los datos completos para cobrar.', (string) $id),
            409,
        );
    }
}
