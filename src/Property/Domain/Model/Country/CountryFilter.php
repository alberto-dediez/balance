<?php

namespace App\Property\Domain\Model\Country;

use App\Shared\Domain\Model\Filter;

class CountryFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?string $iso = null,
        int $limit = 10
    ) {
        $this->limit = $limit;
    }
}