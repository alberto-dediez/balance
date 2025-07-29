<?php

namespace App\Property\Application\Brand\Update;

use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Property\ExternalId;

class UpdateBrandRequest
{
    public function __construct(public BrandId $brandId, public string $name, public ?ExternalId $externalId = null)
    {

    }
}