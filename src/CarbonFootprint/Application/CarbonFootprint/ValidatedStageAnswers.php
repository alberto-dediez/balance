<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;

final class ValidatedStageAnswers
{
    /**
     * @param list<SectionAnswer>            $sectionAnswers
     * @param array<string, ExclusionAnswer> $exclusionsBySource
     */
    public function __construct(
        public readonly array $sectionAnswers,
        public readonly array $exclusionsBySource,
        public readonly ?StageExclusionAnswer $stageExclusion = null,
    ) {}
}
