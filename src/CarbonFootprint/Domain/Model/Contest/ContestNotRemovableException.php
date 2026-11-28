<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

class ContestNotRemovableException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Solo se puede eliminar un concurso en borrador.', 409);
    }
}
