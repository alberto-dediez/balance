<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentCompleted;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;

class MaterialityDecisionInputsMapper
{
    /** @return list<MaterialityDecisionInput> */
    public function fromCompleted(MaterialityAssessmentCompleted $event): array
    {
        $inputs = [];

        foreach ($event->scores() as $score) {
            if (null === $score['verdict'] || null === $score['scorePercent']) {
                continue;
            }

            $inputs[] = new MaterialityDecisionInput(
                topicId: $score['topicId'],
                topicNumber: MaterialityScope::Scope3->value === $score['scope'] ? $score['topicNumber'] : null,
                scorePercent: $score['scorePercent'],
                thresholdPercent: $score['thresholdPercent'],
                forcedByHighMagnitude: $score['forcedByHighMagnitude'],
                verdict: MaterialityVerdict::from($score['verdict']),
            );
        }

        return $inputs;
    }
}
