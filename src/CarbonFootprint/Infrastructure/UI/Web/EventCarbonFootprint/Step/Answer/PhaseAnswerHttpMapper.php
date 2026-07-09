<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionDefinition;
use Symfony\Component\HttpFoundation\Request;

final class PhaseAnswerHttpMapper
{
    public function __construct(private SectionAnswerHttpMapperRegistry $mappers) {}

    /**
     * @param ?list<SectionDefinition> $sections
     *
     * @return list<SectionAnswer>
     */
    public function map(Request $request, Phase $phase, ?array $sections = null): array
    {
        $sectionAnswers = [];

        foreach ($sections ?? $phase->sections() as $section) {
            $mapper = $this->mappers->forActivityType($section->activityType);

            $answers = [];
            foreach ($request->request->all($section->key) as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $answer = $mapper->map($row);

                if (null !== $answer) {
                    $answers[] = $answer;
                }
            }

            $sectionAnswers[] = new SectionAnswer($section, $answers);
        }

        return $sectionAnswers;
    }
}
