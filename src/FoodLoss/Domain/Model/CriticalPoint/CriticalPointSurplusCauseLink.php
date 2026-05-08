<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;

final class CriticalPointSurplusCauseLink
{
    public function __construct(
        private CriticalPointSurplusCauseLinkId $id,
        private CriticalPoint $criticalPoint,
        private SurplusCauseId $surplusCauseId,
    ) {}

    public function id(): CriticalPointSurplusCauseLinkId
    {
        return $this->id;
    }

    public function criticalPoint(): CriticalPoint
    {
        return $this->criticalPoint;
    }

    public function surplusCauseId(): SurplusCauseId
    {
        return $this->surplusCauseId;
    }
}
