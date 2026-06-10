<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class SignatureTokenInvalidException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El enlace de firma no es válido o ha expirado.', 404);
    }
}
