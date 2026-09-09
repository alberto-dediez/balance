<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

class AccountIsNotConsultantException extends \RuntimeException
{
    public function __construct(AccountId $id)
    {
        parent::__construct(
            sprintf(
                'La cuenta %s no está marcada como consultora, por lo que no puede asignarse como tal.',
                (string) $id,
            ),
            409,
        );
    }
}
