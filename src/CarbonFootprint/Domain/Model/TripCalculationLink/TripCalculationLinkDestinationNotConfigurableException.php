<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

class TripCalculationLinkDestinationNotConfigurableException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Solo la calculadora de viajes de la cuenta permite configurar el destino.');
    }
}
