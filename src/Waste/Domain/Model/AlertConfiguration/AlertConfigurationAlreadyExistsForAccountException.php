<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Security\Domain\Model\Account\AccountId;

class AlertConfigurationAlreadyExistsForAccountException extends \RuntimeException
{
    public function __construct(AccountId $accountId)
    {
        parent::__construct(
            sprintf('Ya existe una configuración de alertas para la empresa %s', $accountId),
            409,
        );
    }
}
