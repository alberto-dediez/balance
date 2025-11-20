<?php

namespace App\DataExchange\Application\Importation\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterImportationRequest implements Command
{
    public function __construct(public string $name, public AccountId $accountId)
    {

    }
}
