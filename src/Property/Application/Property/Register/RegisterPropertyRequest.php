<?php

namespace App\Property\Application\Property\Register;

use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\Account\AccountId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\PropertyInfo;

class RegisterPropertyRequest implements Command
{
    public function __construct(public string $name = '', public ?AccountId $accountId = null, public ?PropertyInfo $info = null, public ?ExternalId $externalId = null)
    {
        
    }
}