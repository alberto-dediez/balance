<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

enum EmployeeContributionStatus: string
{
    case Pending  = 'pending';
    case Answered = 'answered';
    case Ignored  = 'ignored';

    public function isPending(): bool
    {
        return self::Pending === $this;
    }

    public function isAnswered(): bool
    {
        return self::Answered === $this;
    }

    public function isIgnored(): bool
    {
        return self::Ignored === $this;
    }
}
