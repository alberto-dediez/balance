<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Import;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportPurchaseServiceResponse implements CommandResponse
{
    public function __construct(
        public PurchaseServiceId $purchaseServiceId,
    ) {}
}
