<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

enum OrganizationalCarbonFootprintStatus: string
{
    case Started        = 'started';
    case Scope1Answered = 'scope_1_answered';
    case Scope2Answered = 'scope_2_answered';
    case Completed      = 'completed';

    public function isScope1Answered(): bool
    {
        return match ($this) {
            self::Scope1Answered, self::Scope2Answered, self::Completed => true,
            default => false,
        };
    }

    public function isScope2Answered(): bool
    {
        return match ($this) {
            self::Scope2Answered, self::Completed => true,
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
            self::Scope2Answered, self::Completed => 3,
        };
    }

    public function activeStepKey(): string
    {
        return match ($this) {
            self::Started        => 'scope-1',
            self::Scope1Answered => 'scope-2',
            self::Scope2Answered, self::Completed => 'results',
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
