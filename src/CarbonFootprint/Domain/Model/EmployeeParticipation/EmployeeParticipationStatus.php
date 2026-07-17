<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeParticipation;

enum EmployeeParticipationStatus: string
{
    case Open   = 'open';
    case Closed = 'closed';

    public function isClosed(): bool
    {
        return self::Closed === $this;
    }
}
