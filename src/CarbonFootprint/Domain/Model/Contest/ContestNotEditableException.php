<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

class ContestNotEditableException extends \RuntimeException
{
    public function __construct(string $message = 'El concurso no se puede modificar en su estado actual.')
    {
        parent::__construct($message, 409);
    }
}
