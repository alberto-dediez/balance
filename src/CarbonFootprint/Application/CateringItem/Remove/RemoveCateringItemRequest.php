<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Remove;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\Shared\Application\CommandBus\Command;

class RemoveCateringItemRequest implements Command
{
    public function __construct(public CateringItemId $cateringItemId) {}
}
