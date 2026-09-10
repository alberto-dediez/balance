<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Reallocate;

use App\Shared\Application\CommandBus\CommandResponse;

final class ReallocateAllUnderallocatedContributionsResponse implements CommandResponse
{
    public function __construct(
        public readonly int $pairsProcessed = 0,
        public readonly int $contributionsCovered = 0,
        public readonly float $reallocatedTonnes = 0.0,
    ) {}
}
