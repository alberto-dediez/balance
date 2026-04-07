<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\Remove;

use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemovePackagingResponse implements CommandResponse
{
    public function __construct(public PackagingId $packagingId) {}
}
