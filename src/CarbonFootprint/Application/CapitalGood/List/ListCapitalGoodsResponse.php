<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\List;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;

class ListCapitalGoodsResponse
{
    /** @param array<CapitalGood> $capitalGoods */
    public function __construct(public array $capitalGoods = [], public int $totalResults = 0) {}
}
