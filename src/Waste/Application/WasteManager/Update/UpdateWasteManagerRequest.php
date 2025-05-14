<?php

namespace App\Waste\Application\WasteManager\Update;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class UpdateWasteManagerRequest
{
    public function __construct(public WasteManagerId $wasteManagerId, public string $name, public bool $isPublic)
    {

    }
}