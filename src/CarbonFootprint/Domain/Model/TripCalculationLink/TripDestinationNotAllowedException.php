<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

class TripDestinationNotAllowedException extends \InvalidArgumentException
{
    public function __construct(string $message = 'El destino elegido no está permitido en esta calculadora.')
    {
        parent::__construct($message);
    }
}
