<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\WasteManager;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class WasteManagerId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
