<?php

namespace App\Property\Domain\Model\Property;

use App\Shared\Domain\Model\Filter;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Property\Domain\Model\Destination\DestinationId;

class PropertyFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?DestinationId $destinationId = null,
        public ?CountryId $countryId = null,
        public ?AccountId $accountId = null,
        int $limit = 10
    ) {
        $this->limit = $limit;
    }
}