<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

class InsufficientTransferableCreditException extends \RuntimeException
{
    public function __construct(CreditPurchaseId $id)
    {
        parent::__construct(
            sprintf('La compra de créditos %s no tiene crédito disponible para transferir.', (string) $id),
            409,
        );
    }
}
