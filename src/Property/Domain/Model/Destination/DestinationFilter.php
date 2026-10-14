<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Destination;

use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Domain\Model\Filter;

class DestinationFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?CountryId $countryId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
