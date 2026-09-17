<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Application\CommandBus\CommandResponse;

class ReplaceCompanyCarbonFootprintScopeAnswersResponse implements CommandResponse
{
    /** @param list<Source> $includedSources */
    public function __construct(
        public int $activities,
        public array $includedSources = [],
        public int $blocks = 0,
    ) {}
}
