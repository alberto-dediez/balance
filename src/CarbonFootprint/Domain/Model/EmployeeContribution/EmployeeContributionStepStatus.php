<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

enum EmployeeContributionStepStatus: string
{
    case Started             = 'started';
    case AssemblyAnswered    = 'assembly_answered';
    case CelebrationAnswered = 'celebration_answered';
    case Completed           = 'completed';

    public function isAssemblyAnswered(): bool
    {
        return match ($this) {
            self::AssemblyAnswered, self::CelebrationAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isCelebrationAnswered(): bool
    {
        return match ($this) {
            self::CelebrationAnswered, self::Completed => true,
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
            self::Started             => 1,
            self::AssemblyAnswered    => 2,
            self::CelebrationAnswered => 3,
            self::Completed           => 3,
        };
    }

    public function activeStepRouteName(): string
    {
        return match ($this) {
            self::Started             => 'public_carbon_footprint_employee_contribution_assembly',
            self::AssemblyAnswered    => 'public_carbon_footprint_employee_contribution_celebration',
            self::CelebrationAnswered => 'public_carbon_footprint_employee_contribution_breakdown',
            self::Completed           => 'public_carbon_footprint_employee_contribution_breakdown',
        };
    }

    private function order(): int
    {
        return match ($this) {
            self::Started             => 0,
            self::AssemblyAnswered    => 1,
            self::CelebrationAnswered => 2,
            self::Completed           => 3,
        };
    }
}
