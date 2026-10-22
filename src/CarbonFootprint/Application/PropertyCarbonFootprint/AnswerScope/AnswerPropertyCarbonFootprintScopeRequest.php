<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerScope;

use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class AnswerPropertyCarbonFootprintScopeRequest implements Command
{
    /**
     * @param list<SectionAnswer>   $sectionAnswers
     * @param list<ExclusionAnswer> $exclusions
     */
    public function __construct(
        public PropertyCarbonFootprintId $propertyCarbonFootprintId,
        public Scope $scope,
        public array $sectionAnswers = [],
        public array $exclusions = [],
        public ?StageExclusionAnswer $stageExclusion = null,
    ) {}
}
