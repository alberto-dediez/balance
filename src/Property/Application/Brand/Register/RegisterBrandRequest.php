<?php

namespace App\Property\Application\Brand\Register;

use App\Property\Domain\Model\Property\ExternalId;

class RegisterBrandRequest
{
    public function __construct(public string $name = '', public ?ExternalId $externalId = null)
    {
        
    }
}