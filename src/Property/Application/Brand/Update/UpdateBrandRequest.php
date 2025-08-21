<?php

namespace App\Property\Application\Brand\Update;

use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Property\ExternalId;

class UpdateBrandRequest implements Command
{
    public function __construct(public BrandId $brandId, public string $name, public ?ExternalId $externalId = null)
    {

    }
}