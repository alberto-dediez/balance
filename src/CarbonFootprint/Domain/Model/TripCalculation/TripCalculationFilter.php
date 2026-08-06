<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Domain\Model\Filter;

class TripCalculationFilter
{
    use Filter;

    public function __construct(
        public ?TripCalculationLinkId $tripCalculationLinkId = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
        $this->orderBy = 'registeredAt';
        $this->orderWay = 'DESC';
    }
}
