<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

class ConsultantHasAssignedAccountsException extends \RuntimeException
{
    public function __construct(AccountId $id, int $assigned)
    {
        parent::__construct(
            sprintf(
                'No se puede desmarcar como consultora la cuenta %s porque tiene %d cuentas asignadas.',
                (string) $id,
                $assigned,
            ),
            409,
        );
    }
}
