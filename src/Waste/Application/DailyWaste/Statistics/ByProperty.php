<?php

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Property\Domain\Model\Property\Property;

class ByProperty
{
    public function __construct(
        public Property $property,
        public float $load,
        public float $percentage,
        /** @var ByMonth[] */
        public array $months = []
    )
    {
    }
}
