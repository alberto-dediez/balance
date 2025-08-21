<?php

namespace App\Property\Application\Country\Register;

use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Property\ExternalId;

class RegisterCountryRequest implements Command
{
    public function __construct(public string $name = '', public ?string $iso = null, public ?ExternalId $externalId = null)
    {
        
    }
}