<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;

class MaterialityDecisionInputMother
{
    public static function omit(?int $topicNumber, float $scorePercent = 38.0, int $thresholdPercent = 50, ?string $topicId = null): MaterialityDecisionInput
    {
        return self::from($topicNumber, $scorePercent, $thresholdPercent, false, MaterialityVerdict::Omit, $topicId);
    }

    public static function calculate(?int $topicNumber, float $scorePercent = 62.5, int $thresholdPercent = 50, bool $forcedByHighMagnitude = false, ?string $topicId = null): MaterialityDecisionInput
    {
        return self::from($topicNumber, $scorePercent, $thresholdPercent, $forcedByHighMagnitude, MaterialityVerdict::Calculate, $topicId);
    }

    public static function from(?int $topicNumber, float $scorePercent, int $thresholdPercent, bool $forcedByHighMagnitude, MaterialityVerdict $verdict, ?string $topicId = null): MaterialityDecisionInput
    {
        return new MaterialityDecisionInput(
            topicId: $topicId ?? sprintf('topic-%s', $topicNumber ?? 'custom'),
            topicNumber: $topicNumber,
            scorePercent: $scorePercent,
            thresholdPercent: $thresholdPercent,
            forcedByHighMagnitude: $forcedByHighMagnitude,
            verdict: $verdict,
        );
    }

    /**
     * @param list<MaterialityDecisionInput> $decisions
     *
     * @return array<string, string>
     */
    public static function commentsFor(array $decisions): array
    {
        $comments = [];

        foreach ($decisions as $decision) {
            $source = null === $decision->topicNumber ? null : Source::ofGhgProtocolCategory($decision->topicNumber);

            if (null !== $source) {
                $comments[$source->value] = sprintf('Análisis de materialidad: relevancia %s %% por debajo del umbral %d %%', number_format($decision->scorePercent, 2, ',', '.'), $decision->thresholdPercent);
            }
        }

        return $comments;
    }
}
