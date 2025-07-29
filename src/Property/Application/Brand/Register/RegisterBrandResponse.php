<?php

namespace App\Property\Application\Brand\Register;

use App\Property\Domain\Model\Brand\BrandId;

class RegisterBrandResponse
{
    public function __construct(public BrandId $brandId, public string $name)
    {
        
    }
}