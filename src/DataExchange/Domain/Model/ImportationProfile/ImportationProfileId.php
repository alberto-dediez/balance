<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\ImportationProfile;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ImportationProfileId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
