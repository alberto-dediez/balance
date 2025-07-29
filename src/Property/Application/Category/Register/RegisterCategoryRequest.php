<?php

namespace App\Property\Application\Category\Register;

use App\Property\Domain\Model\Property\ExternalId;

class RegisterCategoryRequest
{
    public function __construct(public string $name = '', public ?ExternalId $externalId = null)
    {
        
    }
}