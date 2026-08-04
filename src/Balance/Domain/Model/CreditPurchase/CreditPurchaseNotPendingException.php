<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

class CreditPurchaseNotPendingException extends \RuntimeException
{
    public function __construct(CreditPurchaseId $id)
    {
        parent::__construct(sprintf('La compra de créditos %s no está en estado pendiente.', (string) $id), 409);
    }
}
