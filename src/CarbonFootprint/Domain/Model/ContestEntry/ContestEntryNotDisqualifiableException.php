<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

class ContestEntryNotDisqualifiableException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Solo se puede descalificar una inscripción confirmada y con sus datos.', 409);
    }
}
