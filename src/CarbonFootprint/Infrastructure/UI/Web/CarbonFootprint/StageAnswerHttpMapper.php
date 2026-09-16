<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\SectionAnswerHttpMapperRegistry;

final class StageAnswerHttpMapper
{
    public function __construct(private SectionAnswerHttpMapperRegistry $mappers) {}

    /**
     * @param array<array-key, mixed> $data
     * @param list<SectionDefinition> $sections
     *
     * @return list<SectionAnswer>
     */
    public function map(array $data, array $sections): array
    {
        $sectionAnswers = [];

        foreach ($sections as $section) {
            $mapper = $this->mappers->forActivityType($section->activityType);

            $rows = $data[$section->key] ?? [];

            $answers = [];
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $answer = $mapper->map($row);

                    if (null !== $answer) {
                        $answers[] = $answer;
                    }
                }
            }

            $sectionAnswers[] = new SectionAnswer($section, $answers);
        }

        return $sectionAnswers;
    }
}
