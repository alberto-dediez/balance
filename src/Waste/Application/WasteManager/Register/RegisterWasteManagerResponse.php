<?php

namespace App\Waste\Application\WasteManager\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class RegisterWasteManagerResponse implements CommandResponse
{
    public function __construct(
        public WasteManagerId $wasteManagerId, 
        public string $name,
        public bool $isPublic,
        public ?AccountId $accountId = null
    ) {
        
    }
}