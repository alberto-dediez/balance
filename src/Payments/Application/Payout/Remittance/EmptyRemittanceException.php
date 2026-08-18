<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Remittance;

class EmptyRemittanceException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('No hay liquidaciones pendientes que incluir en la remesa.', 409);
    }
}
