<?php

namespace App\Property\Domain\Model\Brand;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class BrandId implements AggregateRootId
{
    use AggregateRootIdTrait;
}