<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

class ContestEntryNotPendingPaymentException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('La inscripción no está pendiente de pago.', 409);
    }
}
