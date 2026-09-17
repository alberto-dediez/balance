<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\ReplaceScopeAnswers;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class ReplacePropertyCarbonFootprintScopeAnswersRequest implements Command
{
    /** @param list<SectionAnswer> $sectionAnswers */
    public function __construct(
        public PropertyCarbonFootprintId $propertyCarbonFootprintId,
        public Scope $scope,
        public array $sectionAnswers = [],
    ) {}
}
