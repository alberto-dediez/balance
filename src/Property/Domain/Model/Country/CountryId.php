<?php

namespace App\Property\Domain\Model\Country;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class CountryId implements AggregateRootId
{
    use AggregateRootIdTrait;
}