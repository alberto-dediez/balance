<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

class ContestNotAcceptingEntriesException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El concurso no admite inscripciones.', 409);
    }
}
