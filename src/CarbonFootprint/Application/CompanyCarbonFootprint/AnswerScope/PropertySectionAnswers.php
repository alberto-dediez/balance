<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerScope;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\Property\Domain\Model\Property\PropertyId;

final class PropertySectionAnswers
{
    /** @param list<SectionAnswer> $sectionAnswers */
    public function __construct(
        public readonly ?PropertyId $propertyId,
        public readonly array $sectionAnswers,
    ) {}
}
