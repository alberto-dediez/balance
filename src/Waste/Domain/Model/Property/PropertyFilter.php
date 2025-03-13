<?php

namespace App\Waste\Domain\Model\Property;

use App\Shared\Domain\Model\Filter;
use App\Security\Domain\Model\Account\AccountId;

class PropertyFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?AccountId $accountId = null
    ) {

    }
}