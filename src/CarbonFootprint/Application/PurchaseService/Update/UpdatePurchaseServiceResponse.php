<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Update;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdatePurchaseServiceResponse implements CommandResponse
{
    public function __construct(
        public PurchaseServiceId $purchaseServiceId,
        public string $name,
    ) {}
}
