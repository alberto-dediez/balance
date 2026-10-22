<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;

final readonly class StageExclusionAnswer
{
    public function __construct(
        public ExclusionReason $reason,
        public ?string $comment = null,
    ) {}
}
