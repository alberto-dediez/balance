<?php

namespace App\Property\Domain\Model\Destination;

use App\Shared\Domain\Model\Filter;
use App\Property\Domain\Model\Country\CountryId;

class DestinationFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?CountryId $countryId = null,
        int $limit = 10
    ) {
        $this->limit = $limit;
    }
}