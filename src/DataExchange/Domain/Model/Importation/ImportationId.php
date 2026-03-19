<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ImportationId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
