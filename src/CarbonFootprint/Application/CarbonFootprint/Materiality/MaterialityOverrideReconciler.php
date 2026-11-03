<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\Materiality;

use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecision;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityOverrideJustificationRequiredException;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Domain\Model\User\UserId;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaterialityOverrideReconciler
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    /**
     * @param array<string, ExclusionAnswer> $exclusionsBySource
     * @param array<string, string>          $justifications
     */
    public function validate(CarbonFootprint $carbonFootprint, CalculationStage $stage, array $exclusionsBySource, ?StageExclusionAnswer $stageExclusion, array $justifications): void
    {
        if (!$this->isScope3($stage) || null !== $stageExclusion) {
            return;
        }

        foreach ($this->propagableDecisions($carbonFootprint) as $source => $decision) {
            if ($decision->recommendsExclusion() === isset($exclusionsBySource[$source->value])) {
                continue;
            }

            if ($decision->isOverridden() || '' !== trim($justifications[$source->value] ?? '')) {
                continue;
            }

            throw new MaterialityOverrideJustificationRequiredException($source, $this->translator->trans('Indica el motivo por el que anulas la recomendación de materialidad de {source}', ['{source}' => $this->translator->trans($source->value, [], 'source')]));
        }
    }

    /** @param array<string, string> $justifications */
    public function reconcile(CarbonFootprint $carbonFootprint, CalculationStage $stage, UserId $userId, array $justifications): void
    {
        if (!$this->isScope3($stage) || $carbonFootprint->isStageExcluded($stage)) {
            return;
        }

        $exclusions = $carbonFootprint->exclusionsFor($stage);

        foreach ($this->propagableDecisions($carbonFootprint) as $source => $decision) {
            if ($decision->recommendsExclusion() === isset($exclusions[$source->value])) {
                $carbonFootprint->revertMaterialityOverride($source);

                continue;
            }

            $justification = trim($justifications[$source->value] ?? '');
            $justification = '' !== $justification ? $justification : $decision->overrideJustification();

            if (null !== $justification && '' !== $justification) {
                $carbonFootprint->overrideMaterialityDecision($source, $userId, $justification);
            }
        }
    }

    /** @return \Generator<Source, MaterialityDecision> */
    private function propagableDecisions(CarbonFootprint $carbonFootprint): \Generator
    {
        foreach ($carbonFootprint->materialityDecisions() as $decision) {
            $source = $decision->source();

            if (null === $source) {
                continue;
            }

            if (!$carbonFootprint->materialitySourceIsPropagable($source)) {
                continue;
            }

            yield $source => $decision;
        }
    }

    private function isScope3(CalculationStage $stage): bool
    {
        return $stage->key() === Scope::Scope3->key();
    }
}
