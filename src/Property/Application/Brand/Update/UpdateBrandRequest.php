<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\Update;

use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Application\CommandBus\Command;

class UpdateBrandRequest implements Command
{
    public function __construct(public BrandId $brandId, public string $name, public ?ExternalId $externalId = null) {}
}
