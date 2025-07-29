<?php

namespace App\Property\Application\Country\Register;

use App\Property\Domain\Model\Property\ExternalId;

class RegisterCountryRequest
{
    public function __construct(public string $name = '', public ?string $iso = null, public ?ExternalId $externalId = null)
    {
        
    }
}