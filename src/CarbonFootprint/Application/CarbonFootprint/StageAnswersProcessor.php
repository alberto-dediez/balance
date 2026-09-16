<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;

final class StageAnswersProcessor
{
    /**
     * @param list<SectionAnswer>   $sectionAnswers
     * @param list<ExclusionAnswer> $exclusions
     */
    public function validate(CalculationStage $stage, array $sectionAnswers, array $exclusions): ValidatedStageAnswers
    {
        $sectionsByKey = [];
        foreach ($stage->sections() as $section) {
            $sectionsByKey[$section->key] = $section;
        }

        $validatedAnswers = [];
        foreach ($sectionAnswers as $sectionAnswer) {
            $definition = $sectionsByKey[$sectionAnswer->definition->key]
                ?? throw new \InvalidArgumentException(sprintf('Section "%s" does not belong to stage "%s".', $sectionAnswer->definition->key, $stage->key()));

            foreach ($sectionAnswer->answers as $answer) {
                if ($answer->type() !== $definition->activityType) {
                    throw new \InvalidArgumentException(sprintf('Answer of type "%s" does not match section "%s" type.', $answer->type()->value, $definition->key));
                }
            }

            $validatedAnswers[] = new SectionAnswer($definition, $sectionAnswer->answers);
        }

        $stageSources = [];
        foreach ($stage->sections() as $section) {
            $stageSources[$section->source->value] = $section->source;
        }

        $exclusionsBySource = [];
        foreach ($exclusions as $exclusion) {
            if (!isset($stageSources[$exclusion->source->value])) {
                throw new \InvalidArgumentException(sprintf('Source "%s" does not belong to stage "%s".', $exclusion->source->value, $stage->key()));
            }

            $exclusionsBySource[$exclusion->source->value] = $exclusion;
        }

        foreach ($validatedAnswers as $sectionAnswer) {
            if ([] !== $sectionAnswer->answers && isset($exclusionsBySource[$sectionAnswer->definition->source->value])) {
                throw new \InvalidArgumentException(sprintf('Section "%s" cannot have answers because its source "%s" is excluded.', $sectionAnswer->definition->key, $sectionAnswer->definition->source->value));
            }
        }

        return new ValidatedStageAnswers($validatedAnswers, $exclusionsBySource);
    }

    /** @param array<string, ExclusionAnswer> $exclusionsBySource */
    public function applyExclusions(CarbonFootprint $carbonFootprint, CalculationStage $stage, array $exclusionsBySource): void
    {
        $stageSources = [];
        foreach ($stage->sections() as $section) {
            $stageSources[$section->source->value] = $section->source;
        }

        foreach ($stageSources as $sourceValue => $source) {
            if (isset($exclusionsBySource[$sourceValue])) {
                $exclusion = $exclusionsBySource[$sourceValue];
                $carbonFootprint->excludeSource($stage, $source, $exclusion->reason, $exclusion->comment);

                continue;
            }

            $carbonFootprint->includeSource($stage, $source);
        }
    }
}
