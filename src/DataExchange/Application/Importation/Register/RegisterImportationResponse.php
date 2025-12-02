<?php

namespace App\DataExchange\Application\Importation\Register;

use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterImportationResponse implements CommandResponse
{
    public function __construct(public ImportationId $importationId, public string $name, public AccountId $accountId, public ?UserId $registeredBy)
    {

    }
}
