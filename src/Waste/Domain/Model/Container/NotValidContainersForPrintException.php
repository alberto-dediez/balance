<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

class NotValidContainersForPrintException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'No hay contenedores disponibles',
            400,
        );
    }
}
