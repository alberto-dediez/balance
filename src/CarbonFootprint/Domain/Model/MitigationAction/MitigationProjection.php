<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

final class MitigationProjection
{
    /**
     * @param list<int>                        $years
     * @param list<MitigationProjectionSource> $sources
     * @param array<int, float>                $totalByYear
     * @param array<int, float>                $interannualReductionByYear
     */
    public function __construct(
        public readonly int $baseYear,
        public readonly int $targetYear,
        public readonly array $years,
        public readonly array $sources,
        public readonly array $totalByYear,
        public readonly array $interannualReductionByYear,
    ) {}
}
