<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Provider;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ProviderId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
