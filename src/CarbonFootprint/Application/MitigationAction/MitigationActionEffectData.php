<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;

class MitigationActionEffectData
{
    public function __construct(
        public Source $source,
        public string $itemId,
        public float $reduction,
    ) {}
}
