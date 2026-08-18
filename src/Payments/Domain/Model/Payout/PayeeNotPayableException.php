<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

class PayeeNotPayableException extends \RuntimeException
{
    public function __construct(string $payoutNumber)
    {
        parent::__construct(
            sprintf('La liquidación %s no puede entrar en la remesa: su beneficiario no está listo.', $payoutNumber),
            409,
        );
    }
}
