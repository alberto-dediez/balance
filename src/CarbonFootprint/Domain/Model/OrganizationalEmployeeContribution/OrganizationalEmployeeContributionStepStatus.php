<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

enum OrganizationalEmployeeContributionStepStatus: string
{
    case Started           = 'started';
    case CommutingAnswered = 'commuting_answered';
    case Completed         = 'completed';

    public function isCommutingAnswered(): bool
    {
        return match ($this) {
            self::CommutingAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isCompleted(): bool
    {
        return self::Completed === $this;
    }

    public function isBefore(self $other): bool
    {
        return $this->order() < $other->order();
    }

    public function activeStepNumber(): int
    {
        return match ($this) {
            self::Started           => 1,
            self::CommutingAnswered => 2,
            self::Completed         => 2,
        };
    }

    public function activeStepRouteName(): string
    {
        return match ($this) {
            self::Started           => 'public_organizational_employee_contribution_commuting',
            self::CommutingAnswered => 'public_organizational_employee_contribution_business_travel',
            self::Completed         => 'public_organizational_employee_contribution_answered',
        };
    }

    private function order(): int
    {
        return match ($this) {
            self::Started           => 0,
            self::CommutingAnswered => 1,
            self::Completed         => 2,
        };
    }
}
