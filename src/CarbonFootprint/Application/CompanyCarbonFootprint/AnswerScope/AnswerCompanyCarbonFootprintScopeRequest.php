<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope;

use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Application\CommandBus\Command;

class AnswerCompanyCarbonFootprintScopeRequest implements Command
{
    /**
     * @param list<PropertySectionAnswers>        $answersByProperty
     * @param list<ExclusionAnswer>               $exclusions
     * @param list<NonConsolidatedPropertyAnswer> $nonConsolidatedProperties
     */
    public function __construct(
        public CompanyCarbonFootprintId $companyCarbonFootprintId,
        public Scope $scope,
        public array $answersByProperty = [],
        public array $exclusions = [],
        public ?StageExclusionAnswer $stageExclusion = null,
        public array $nonConsolidatedProperties = [],
    ) {}
}
