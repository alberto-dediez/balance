<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\Update;

use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateBrandResponse implements CommandResponse
{
    public function __construct(public BrandId $brandId, public string $name) {}
}
