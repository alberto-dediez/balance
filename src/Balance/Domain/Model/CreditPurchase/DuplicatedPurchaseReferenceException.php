<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

class DuplicatedPurchaseReferenceException extends \RuntimeException
{
    public function __construct(string $reference)
    {
        parent::__construct(sprintf('El identificador %s ya está registrado en otra compra.', $reference), 409);
    }
}
