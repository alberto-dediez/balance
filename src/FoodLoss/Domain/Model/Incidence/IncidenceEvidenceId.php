<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class IncidenceEvidenceId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
