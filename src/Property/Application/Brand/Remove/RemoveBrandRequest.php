<?php

namespace App\Property\Application\Brand\Remove;

use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Application\CommandBus\Command;

class RemoveBrandRequest implements Command
{
    public function __construct(public BrandId $brandId)
    {

    }
}