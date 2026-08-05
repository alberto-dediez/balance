<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class AttendeeSurveyResponseLegId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
