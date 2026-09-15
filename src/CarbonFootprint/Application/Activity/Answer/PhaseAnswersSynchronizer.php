<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintCountryResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\User\User;

final class PhaseAnswersSynchronizer
{
    public function __construct(
        private ActivityAnswerHandlerRegistry $handlers,
        private EventCarbonFootprintCountryResolver $countryResolver,
    ) {}

    /**
     * @param list<SectionAnswer>      $sectionAnswers
     * @param ?list<SectionDefinition> $sections
     */
    public function sync(EventCarbonFootprint $eventCarbonFootprint, Phase $phase, array $sectionAnswers, User $user, ?array $sections = null, ?ProviderId $providerId = null, bool $fromEmployees = false): void
    {
        $sections ??= $phase->sections();

        if ([] === $sections) {
            return;
        }

        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId()
            ?? throw new \InvalidArgumentException(sprintf('EventCarbonFootprint "%s" has no emission factor group configured.', $eventCarbonFootprint->id()));

        $accountId = $eventCarbonFootprint->accountId()
            ?? throw new \InvalidArgumentException(sprintf('EventCarbonFootprint "%s" has no account configured.', $eventCarbonFootprint->id()));

        $year = $eventCarbonFootprint->calculationYear();

        $countryId = $this->countryResolver->resolve($eventCarbonFootprint);

        $answersBySource = [];
        foreach ($sections as $section) {
            $answersBySource[$section->source->value] ??= [];
        }

        foreach ($sectionAnswers as $sectionAnswer) {
            $answersBySource[$sectionAnswer->definition->source->value][] = $sectionAnswer;
        }

        foreach ($answersBySource as $sourceValue => $sourceSections) {
            $source = Source::from($sourceValue);
            $existingIds = $eventCarbonFootprint->activities()
                ->filterByPhase($phase)
                ->filterBySource($source)
                ->filterByProviderId($providerId)
                ->filterByFromEmployees($fromEmployees)
                ->activityIds();

            $keptIds = [];

            foreach ($sourceSections as $sectionAnswer) {
                $handler = $this->handlers->forActivityType($sectionAnswer->definition->activityType);

                foreach ($sectionAnswer->answers as $answer) {
                    $existingActivityId = $answer->existingActivityId;

                    if (null !== $existingActivityId && self::contains($existingIds, $existingActivityId)) {
                        $handler->update($answer, $year, $emissionFactorGroupId, $user, $countryId);
                        $keptIds[] = $existingActivityId;

                        continue;
                    }

                    $activityId = $handler->register($answer, $year, $emissionFactorGroupId, $accountId, $user, $countryId);
                    $eventCarbonFootprint->addActivity($activityId, $phase, $source, $providerId, $fromEmployees);
                    $keptIds[] = $activityId;
                }
            }

            $eventCarbonFootprint->retainActivities($phase, $source, $keptIds, $providerId, $fromEmployees);
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
