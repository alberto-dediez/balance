<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\DailyWaste;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class DailyWasteId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
