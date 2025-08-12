<?php

namespace App\Security\Domain\Model\User;

use App\Security\Domain\Model\User\RecoverToken;

class RecoverTokenInvalidException extends \RuntimeException
{
    public function __construct(RecoverToken $recoverToken)
    {
        parent::__construct(
            sprintf("El token %s es incorrecto. Fecha de expiración %s", $recoverToken->token(), $recoverToken->expirateAt()->format('H:i d/m/Y')),
            403
        );
    }
}