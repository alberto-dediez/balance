<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;

final readonly class CriticalPointSurplusCauseLink
{
    public function __construct(
        private CriticalPoint $criticalPoint,
        private SurplusCauseId $surplusCauseId,
    ) {}

    public function criticalPoint(): CriticalPoint
    {
        return $this->criticalPoint;
    }

    public function surplusCauseId(): SurplusCauseId
    {
        return $this->surplusCauseId;
    }
}
