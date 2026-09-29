<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\Update;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureEffect;

class MitigationMeasureEffectData
{
    public function __construct(
        public ?Source $source = null,
        public ?float $minimumReduction = null,
        public ?float $maximumReduction = null,
    ) {}

    public static function fromMitigationMeasureEffect(MitigationMeasureEffect $effect): self
    {
        return new self(
            source: $effect->source(),
            minimumReduction: $effect->minimumReduction(),
            maximumReduction: $effect->maximumReduction(),
        );
    }

    /**
     * @param array<MitigationMeasureEffectData> $effects
     *
     * @return list<array{source: Source, minimumReduction: float, maximumReduction: float}>
     */
    public static function toEffects(array $effects): array
    {
        $result = [];

        foreach ($effects as $effect) {
            if (null === $effect->source) {
                throw new \InvalidArgumentException('Mitigation measure effect source is required');
            }

            $result[] = [
                'source' => $effect->source,
                'minimumReduction' => (float) $effect->minimumReduction,
                'maximumReduction' => (float) $effect->maximumReduction,
            ];
        }

        return $result;
    }
}
