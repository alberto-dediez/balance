<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class MaterialityAssessmentAnswerId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
