<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

class ApiTokenExpirationInvalidException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('La fecha de caducidad del token de API debe ser futura.', 400);
    }
}
