<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ProviderContribution;

enum ProviderContributionStepStatus: string
{
    case Started             = 'started';
    case GeneralAnswered     = 'general_answered';
    case AssemblyAnswered    = 'assembly_answered';
    case CelebrationAnswered = 'celebration_answered';
    case Completed           = 'completed';

    public function isGeneralAnswered(): bool
    {
        return match ($this) {
            self::GeneralAnswered, self::AssemblyAnswered, self::CelebrationAnswered, self::Completed => true,
            default => false,
        };
    }

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
            self::GeneralAnswered     => 2,
            self::AssemblyAnswered    => 3,
            self::CelebrationAnswered => 4,
            self::Completed           => 4,
        };
    }

    public function activeStepRouteName(): string
    {
        return match ($this) {
            self::Started             => 'public_carbon_footprint_contribution_general',
            self::GeneralAnswered     => 'public_carbon_footprint_contribution_assembly',
            self::AssemblyAnswered    => 'public_carbon_footprint_contribution_celebration',
            self::CelebrationAnswered => 'public_carbon_footprint_contribution_breakdown',
            self::Completed           => 'public_carbon_footprint_contribution_breakdown',
        };
    }

    private function order(): int
    {
        return match ($this) {
            self::Started             => 0,
            self::GeneralAnswered     => 1,
            self::AssemblyAnswered    => 2,
            self::CelebrationAnswered => 3,
            self::Completed           => 4,
        };
    }
}
