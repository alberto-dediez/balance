<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\List;

class ListMerchandisingItemsResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem> $merchandisingItems */
    public function __construct(public array $merchandisingItems = [], public int $totalResults = 0) {}
}
