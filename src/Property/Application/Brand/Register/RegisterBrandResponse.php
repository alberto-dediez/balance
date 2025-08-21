<?php

namespace App\Property\Application\Brand\Register;

use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterBrandResponse implements CommandResponse
{
    public function __construct(public BrandId $brandId, public string $name)
    {
        
    }
}