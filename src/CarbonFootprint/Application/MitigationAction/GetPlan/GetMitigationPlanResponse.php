<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\GetPlan;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjection;

class GetMitigationPlanResponse
{
    /**
     * @param list<MitigationAction>         $actions
     * @param list<MitigationEmissionSource> $emissionSources
     */
    public function __construct(
        public array $actions,
        public array $emissionSources,
        public ?MitigationProjection $projection,
        public bool $hasPreviousFootprint,
    ) {}
}
