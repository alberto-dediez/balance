<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\List;

class ListCateringItemsResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\CateringItem\CateringItem> $cateringItems */
    public function __construct(public array $cateringItems = [], public int $totalResults = 0) {}
}
