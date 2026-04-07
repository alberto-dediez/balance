<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\Remove;

use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\Shared\Application\CommandBus\Command;

class RemovePackagingRequest implements Command
{
    public function __construct(public PackagingId $packagingId) {}
}
