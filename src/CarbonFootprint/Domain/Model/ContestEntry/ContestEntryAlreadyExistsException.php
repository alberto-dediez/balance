<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

class ContestEntryAlreadyExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ya hay una inscripción en este concurso con estos datos.', 409);
    }
}
