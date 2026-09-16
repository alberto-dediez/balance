<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\User\User;

final class StageAnswersSynchronizer
{
    public function __construct(
        private ActivityAnswerHandlerRegistry $handlers,
        private CarbonFootprintCountryResolver $countryResolver,
    ) {}

    /**
     * @param list<SectionAnswer>      $sectionAnswers
     * @param ?list<SectionDefinition> $sections
     */
    public function sync(CarbonFootprint $carbonFootprint, CalculationStage $stage, array $sectionAnswers, User $user, ?array $sections = null, ?ProviderId $providerId = null, bool $fromEmployees = false, ?PropertyId $propertyId = null): void
    {
        $sections ??= $stage->sections();

        if ([] === $sections) {
            return;
        }

        $emissionFactorGroupId = $carbonFootprint->emissionFactorGroupId()
            ?? throw new \InvalidArgumentException(sprintf('Carbon footprint "%s" has no emission factor group configured.', $carbonFootprint->id()));

        $accountId = $carbonFootprint->accountId()
            ?? throw new \InvalidArgumentException(sprintf('Carbon footprint "%s" has no account configured.', $carbonFootprint->id()));

        $year = $carbonFootprint->calculationYear();

        $countryId = $this->countryResolver->resolve($carbonFootprint, $propertyId);

        $answersBySource = [];
        foreach ($sections as $section) {
            $answersBySource[$section->source->value] ??= [];
        }

        foreach ($sectionAnswers as $sectionAnswer) {
            $answersBySource[$sectionAnswer->definition->source->value][] = $sectionAnswer;
        }

        foreach ($answersBySource as $sourceValue => $sourceSections) {
            $placement = new ActivityPlacement(
                stage: $stage,
                source: Source::from((string) $sourceValue),
                providerId: $providerId,
                fromEmployees: $fromEmployees,
                propertyId: $propertyId,
            );

            $existingIds = $carbonFootprint->activityIds($placement);

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
                    $carbonFootprint->addActivity($activityId, $placement);
                    $keptIds[] = $activityId;
                }
            }

            $carbonFootprint->retainActivities($placement, $keptIds);
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
