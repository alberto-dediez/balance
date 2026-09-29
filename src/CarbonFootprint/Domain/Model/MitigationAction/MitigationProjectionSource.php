<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

final class MitigationProjectionSource
{
    /**
     * @param array<int, float> $rateByYear
     * @param array<int, float> $reductionByYear
     * @param array<int, float> $expectedByYear
     */
    public function __construct(
        public readonly MitigationEmissionSource $emissionSource,
        public readonly array $rateByYear,
        public readonly array $reductionByYear,
        public readonly array $expectedByYear,
        public readonly bool $hasReductions,
    ) {}
}
