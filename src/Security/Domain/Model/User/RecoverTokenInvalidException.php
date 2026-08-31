<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

class RecoverTokenInvalidException extends \RuntimeException
{
    public function __construct(RecoverToken $recoverToken, ?\DateTimeImmutable $expirateAt = null)
    {
        parent::__construct(
            sprintf('El token %s es incorrecto. Fecha de expiración %s', $recoverToken->token(), $expirateAt?->format('H:i d/m/Y') ?? 'desconocida'),
            403,
        );
    }
}
