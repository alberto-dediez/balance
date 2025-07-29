<?php

namespace App\Property\Application\Brand\Update;

use App\Property\Domain\Model\Brand\BrandId;

class UpdateBrandResponse
{
    public function __construct(public BrandId $brandId, public string $name)
    {
        
    }
}