<?php

namespace App\Waste\Application\WasteManager\Register;

use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\Account\AccountId;

class RegisterWasteManagerRequest implements Command
{
    public function __construct(public string $name = '', public bool $isPublic = true, public ?AccountId $accountId = null)
    {
        
    }
}