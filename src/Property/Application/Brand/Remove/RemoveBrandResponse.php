<?php

namespace App\Property\Application\Brand\Remove;

use App\Property\Domain\Model\Brand\BrandId;

class RemoveBrandResponse
{
    public function __construct(public BrandId $brandId)
    {
        
    }
}