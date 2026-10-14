<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\Remove;

use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveBrandResponse implements CommandResponse
{
    public function __construct(public BrandId $brandId) {}
}
