<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\List;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;

class ListPurchaseServicesResponse
{
    /** @param array<PurchaseService> $purchaseServices */
    public function __construct(public array $purchaseServices = [], public int $totalResults = 0) {}
}
