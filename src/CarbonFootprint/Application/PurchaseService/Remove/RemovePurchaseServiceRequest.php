<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Remove;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\Shared\Application\CommandBus\Command;

class RemovePurchaseServiceRequest implements Command
{
    public function __construct(public PurchaseServiceId $purchaseServiceId) {}
}
