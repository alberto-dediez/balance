<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

class ConsultantCannotHaveConsultantException extends \RuntimeException
{
    public function __construct(AccountId $id)
    {
        parent::__construct(
            sprintf(
                'La cuenta %s es consultora, por lo que no puede tener una consultora asignada.',
                (string) $id,
            ),
            409,
        );
    }
}
