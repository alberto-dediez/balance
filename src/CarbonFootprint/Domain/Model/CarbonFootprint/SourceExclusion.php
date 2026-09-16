<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

interface SourceExclusion
{
    public function stage(): CalculationStage;

    public function source(): Source;

    public function reason(): ExclusionReason;

    public function comment(): ?string;
}
