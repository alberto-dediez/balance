<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest;

class ContactRequestNotAvailableException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('No procede registrar una solicitud de contacto para este cálculo.', 409);
    }
}
