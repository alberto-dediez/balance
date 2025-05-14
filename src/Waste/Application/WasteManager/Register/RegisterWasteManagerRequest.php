<?php

namespace App\Waste\Application\WasteManager\Register;

use App\Security\Domain\Model\Account\AccountId;

class RegisterWasteManagerRequest
{
    public function __construct(public string $name = '', public bool $isPublic = true, public ?AccountId $accountId = null)
    {
        
    }
}