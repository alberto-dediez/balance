<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\DistributorAssignment;

use App\Security\Domain\Model\Account\AccountId;

class AccountIsNotDistributorException extends \RuntimeException
{
    public function __construct(AccountId $id)
    {
        parent::__construct(
            sprintf(
                'La cuenta %s no es distribuidora.',
                (string) $id,
            ),
            409,
        );
    }
}
