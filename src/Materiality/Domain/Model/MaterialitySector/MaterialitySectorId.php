<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySector;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class MaterialitySectorId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
