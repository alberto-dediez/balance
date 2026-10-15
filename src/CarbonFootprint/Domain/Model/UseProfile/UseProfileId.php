<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\UseProfile;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class UseProfileId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
