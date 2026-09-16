<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

final class ExclusionAnswer
{
    public function __construct(
        public readonly Source $source,
        public readonly ExclusionReason $reason,
        public readonly ?string $comment = null,
    ) {}
}
