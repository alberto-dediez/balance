<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

enum OrganizationalCarbonFootprintStatus: string
{
    case Started        = 'started';
    case Scope1Answered = 'scope_1_answered';
    case Scope2Answered = 'scope_2_answered';
    case MaterialityAnswered = 'materiality_answered';
    case Scope3Answered    = 'scope_3_answered';
    case ProvidersAnswered = 'providers_answered';
    case EmployeesAnswered = 'employees_answered';
    case Completed         = 'completed';

    public function isScope1Answered(): bool
    {
        return match ($this) {
            self::Scope1Answered, self::Scope2Answered, self::MaterialityAnswered, self::Scope3Answered, self::ProvidersAnswered, self::EmployeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isScope2Answered(): bool
    {
        return match ($this) {
            self::Scope2Answered, self::MaterialityAnswered, self::Scope3Answered, self::ProvidersAnswered, self::EmployeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isMaterialityAnswered(): bool
    {
        return match ($this) {
            self::MaterialityAnswered, self::Scope3Answered, self::ProvidersAnswered, self::EmployeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isScope3Answered(): bool
    {
        return match ($this) {
            self::Scope3Answered, self::ProvidersAnswered, self::EmployeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isProvidersAnswered(): bool
    {
        return match ($this) {
            self::ProvidersAnswered, self::EmployeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isEmployeesAnswered(): bool
    {
        return match ($this) {
            self::EmployeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isCompleted(): bool
    {
        return self::Completed === $this;
    }

    public function stepNumber(): int
    {
        return match ($this) {
            self::Started        => 1,
            self::Scope1Answered => 2,
            self::Scope2Answered => 3,
            self::MaterialityAnswered => 4,
            self::Scope3Answered    => 5,
            self::ProvidersAnswered => 6,
            self::EmployeesAnswered => 7,
            self::Completed         => 8,
        };
    }

    public function activeStepKey(): string
    {
        return match ($this) {
            self::Started        => 'scope-1',
            self::Scope1Answered => 'scope-2',
            self::Scope2Answered => 'materiality',
            self::MaterialityAnswered => 'scope-3',
            self::Scope3Answered    => 'providers',
            self::ProvidersAnswered => 'employees',
            self::EmployeesAnswered => 'mitigation-plan',
            self::Completed         => 'results',
        };
    }

    public function style(): string
    {
        return match ($this) {
            self::Completed => 'bg-success',
            default         => 'bg-info',
        };
    }

    public function advancedTo(self $candidate): self
    {
        if (self::Completed === $this) {
            return self::Completed;
        }

        return $candidate->stepNumber() > $this->stepNumber() ? $candidate : $this;
    }
}
