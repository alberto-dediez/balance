<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreConsumption;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;

final readonly class BioscoreConsumptionAnswers
{
    /**
     * @param list<SectionAnswer>              $sectionAnswers
     * @param list<SkippedBioscoreConsumption> $skipped
     */
    public function __construct(
        public array $sectionAnswers,
        public array $skipped,
    ) {}

    public function answerCount(): int
    {
        $count = 0;

        foreach ($this->sectionAnswers as $sectionAnswer) {
            $count += count($sectionAnswer->answers);
        }

        return $count;
    }
}
