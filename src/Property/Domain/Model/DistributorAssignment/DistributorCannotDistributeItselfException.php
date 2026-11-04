<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\DistributorAssignment;

use App\Security\Domain\Model\Account\AccountId;

class DistributorCannotDistributeItselfException extends \RuntimeException
{
    public function __construct(AccountId $id)
    {
        parent::__construct(
            sprintf(
                'La cuenta %s no puede ser distribuidora de sí misma ni de sus propias propiedades.',
                (string) $id,
            ),
            409,
        );
    }
}
