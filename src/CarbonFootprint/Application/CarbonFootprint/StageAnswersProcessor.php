<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class StageAnswersProcessor
{
    /**
     * @param list<SectionAnswer>   $sectionAnswers
     * @param list<ExclusionAnswer> $exclusions
     */
    public function validate(CalculationStage $stage, array $sectionAnswers, array $exclusions, ?StageExclusionAnswer $stageExclusion = null): ValidatedStageAnswers
    {
        if (null !== $stageExclusion) {
            if (!$stage instanceof Scope || !$stage->isExcludable()) {
                throw new \InvalidArgumentException(sprintf('Stage "%s" cannot be excluded.', $stage->key()));
            }

            foreach ($sectionAnswers as $sectionAnswer) {
                if ([] !== $sectionAnswer->answers) {
                    throw new \InvalidArgumentException(sprintf('Stage "%s" cannot have answers because it is excluded.', $stage->key()));
                }
            }

            if ([] !== $exclusions) {
                throw new \InvalidArgumentException(sprintf('Stage "%s" cannot have source exclusions because it is excluded.', $stage->key()));
            }
        }

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

        $stageSources = $this->stageSources($stage);

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

        return new ValidatedStageAnswers($validatedAnswers, $exclusionsBySource, $stageExclusion);
    }

    /** @param array<string, ExclusionAnswer> $exclusionsBySource */
    public function applyExclusions(CarbonFootprint $carbonFootprint, CalculationStage $stage, array $exclusionsBySource): void
    {
        $stageSources = $this->stageSources($stage);

        foreach ($stageSources as $sourceValue => $source) {
            if (isset($exclusionsBySource[$sourceValue])) {
                $exclusion = $exclusionsBySource[$sourceValue];
                $carbonFootprint->excludeSource($stage, $source, $exclusion->reason, $exclusion->comment);

                continue;
            }

            $carbonFootprint->includeSource($stage, $source);
        }
    }

    public function applyStageExclusion(CarbonFootprint $carbonFootprint, CalculationStage $stage, ?StageExclusionAnswer $answer): void
    {
        if (null !== $answer) {
            $carbonFootprint->excludeStage($stage, $answer->reason, $answer->comment);

            return;
        }

        if ($stage instanceof Scope && $stage->isExcludable()) {
            $carbonFootprint->includeStage($stage);
        }
    }

    /** @return array<string, Source> */
    private function stageSources(CalculationStage $stage): array
    {
        $sources = $stage instanceof Scope
            ? $stage->allSources()
            : array_map(static fn(SectionDefinition $section): Source => $section->source, $stage->sections());

        $indexed = [];
        foreach ($sources as $source) {
            $indexed[$source->value] = $source;
        }

        return $indexed;
    }
}
