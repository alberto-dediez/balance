<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\ReplaceScopeAnswers;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope\PropertySectionAnswers;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Application\CommandBus\Command;

class ReplaceCompanyCarbonFootprintScopeAnswersRequest implements Command
{
    /** @param list<PropertySectionAnswers> $answersByProperty */
    public function __construct(
        public CompanyCarbonFootprintId $companyCarbonFootprintId,
        public Scope $scope,
        public array $answersByProperty = [],
    ) {}
}
