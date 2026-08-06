<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

class TripCalculationLinkTokenInvalidException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El enlace de la calculadora no es válido.', 404);
    }
}
