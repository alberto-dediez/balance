<?php

namespace App\Waste\Application\WasteManager\Register;

use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Security\Domain\Model\Account\AccountId;

class RegisterWasteManagerResponse
{
    public function __construct(
        public WasteManagerId $wasteManagerId, 
        public string $name,
        public bool $isPublic,
        public ?AccountId $accountId = null
    ) {
        
    }
}