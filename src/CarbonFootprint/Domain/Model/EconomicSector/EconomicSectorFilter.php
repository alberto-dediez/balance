<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

use App\Shared\Domain\Model\Filter;

class EconomicSectorFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?string $classificationCode = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
