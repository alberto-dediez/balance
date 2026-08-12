<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class PublicLandingId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
