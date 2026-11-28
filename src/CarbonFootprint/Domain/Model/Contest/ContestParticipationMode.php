<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

enum ContestParticipationMode: string
{
    case Calculation = 'calculation';

    case Compensation = 'compensation';

    public function isCalculation(): bool
    {
        return self::Calculation === $this;
    }

    public function isCompensation(): bool
    {
        return self::Compensation === $this;
    }
}
