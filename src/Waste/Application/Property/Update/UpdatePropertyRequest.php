<?php

namespace App\Waste\Application\Property\Update;

use App\Security\Domain\Model\Account\Account;
use App\Waste\Domain\Model\Property\PropertyId;

class UpdatePropertyRequest
{
    public function __construct(public PropertyId $propertyId, public string $name, public ?Account $account)
    {

    }
}