<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class PropertyId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
