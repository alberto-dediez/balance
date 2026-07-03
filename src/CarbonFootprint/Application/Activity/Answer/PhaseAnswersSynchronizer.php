<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\Security\Domain\Model\User\User;

final class PhaseAnswersSynchronizer
{
    public function __construct(private ActivityAnswerHandlerRegistry $handlers) {}

    /** @param list<SectionAnswer> $sectionAnswers */
    public function sync(EventCarbonFootprint $eventCarbonFootprint, Phase $phase, array $sectionAnswers, User $user): void
    {
        if ([] === $phase->sections()) {
            return;
        }

        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId()
            ?? throw new \InvalidArgumentException(sprintf('EventCarbonFootprint "%s" has no emission factor group configured.', $eventCarbonFootprint->id()));

        $year = $eventCarbonFootprint->calculationYear();

        $answersBySource = [];
        foreach ($phase->sections() as $section) {
            $answersBySource[$section->source->value] ??= [];
        }

        foreach ($sectionAnswers as $sectionAnswer) {
            $answersBySource[$sectionAnswer->definition->source->value][] = $sectionAnswer;
        }

        foreach ($answersBySource as $sourceValue => $sections) {
            $source = Source::from($sourceValue);
            $existingIds = $eventCarbonFootprint->activities()
                ->filterByPhase($phase)
                ->filterBySource($source)
                ->activityIds();

            $keptIds = [];

            foreach ($sections as $sectionAnswer) {
                $handler = $this->handlers->forActivityType($sectionAnswer->definition->activityType);

                foreach ($sectionAnswer->answers as $answer) {
                    $existingActivityId = $answer->existingActivityId;

                    if (null !== $existingActivityId && self::contains($existingIds, $existingActivityId)) {
                        $handler->update($answer, $year, $emissionFactorGroupId, $user);
                        $keptIds[] = $existingActivityId;

                        continue;
                    }

                    $activityId = $handler->register($answer, $year, $emissionFactorGroupId, $user);
                    $eventCarbonFootprint->addActivity($activityId, $phase, $source);
                    $keptIds[] = $activityId;
                }
            }

            $eventCarbonFootprint->retainActivities($phase, $source, $keptIds);
        }
    }

    /** @param ActivityId[] $ids */
    private static function contains(array $ids, ActivityId $id): bool
    {
        foreach ($ids as $candidate) {
            if ($candidate->equals($id)) {
                return true;
            }
        }

        return false;
    }
}
