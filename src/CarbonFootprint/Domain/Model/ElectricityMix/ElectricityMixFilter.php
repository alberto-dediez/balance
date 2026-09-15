<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMix;

use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Domain\Model\Filter;

class ElectricityMixFilter
{
    use Filter;

    public function __construct(
        public ?CountryId $countryId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
