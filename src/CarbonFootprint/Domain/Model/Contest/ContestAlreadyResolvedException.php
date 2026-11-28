<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

class ContestAlreadyResolvedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El concurso ya está resuelto.', 409);
    }
}
