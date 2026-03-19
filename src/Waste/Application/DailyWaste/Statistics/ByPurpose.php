<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\Statistics;

use App\Waste\Domain\Model\Container\Purpose;

class ByPurpose
{
    public function __construct(
        public Purpose $purpose,
        public float $load,
        public float $percentage,
    ) {}
}
