<?php

namespace App\Waste\Domain\Model\Property;

use App\Shared\Domain\Model\Filter;
use App\Security\Domain\Model\Account\Account;

class PropertyFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?Account $account = null
    ) {

    }
}