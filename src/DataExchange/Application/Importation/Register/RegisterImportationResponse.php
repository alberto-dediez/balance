<?php

namespace App\DataExchange\Application\Importation\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterImportationResponse implements CommandResponse
{
    public function __construct(public AccountId $accountId, public string $name)
    {

    }
}
