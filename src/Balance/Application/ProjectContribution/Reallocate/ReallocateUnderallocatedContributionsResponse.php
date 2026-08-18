<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Reallocate;

final class ReallocateUnderallocatedContributionsResponse
{
    public function __construct(
        public readonly int $contributionsCovered = 0,
        public readonly float $reallocatedTonnes = 0.0,
    ) {}
}
