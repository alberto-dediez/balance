<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Register;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterPurchaseServiceResponse implements CommandResponse
{
    public function __construct(
        public PurchaseServiceId $purchaseServiceId,
        public string $name,
    ) {}
}
