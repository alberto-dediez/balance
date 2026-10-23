<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class PropertyFilter
{
    use Filter;

    /** @param list<AccountId>|null $accountIds */
    public function __construct(
        public ?string $name = null,
        public ?DestinationId $destinationId = null,
        public ?CountryId $countryId = null,
        public ?AccountId $accountId = null,
        public ?array $accountIds = null,
        public ?OperatingRegime $operatingRegime = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
