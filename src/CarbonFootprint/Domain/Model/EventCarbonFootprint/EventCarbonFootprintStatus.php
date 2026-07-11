<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

enum EventCarbonFootprintStatus: string
{
    case Started             = 'started';
    case MaterialsAnswered   = 'materials_answered';
    case AssemblyAnswered    = 'assembly_answered';
    case CelebrationAnswered = 'celebration_answered';
    case BreakdownAnswered   = 'breakdown_answered';
    case ProvidersAnswered   = 'providers_answered';
    case AttendeesAnswered   = 'attendees_answered';
    case Completed           = 'completed';

    public function isMaterialsAnswered(): bool
    {
        return match ($this) {
            self::MaterialsAnswered, self::AssemblyAnswered, self::CelebrationAnswered,
            self::BreakdownAnswered, self::ProvidersAnswered, self::AttendeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isAssemblyAnswered(): bool
    {
        return match ($this) {
            self::AssemblyAnswered, self::CelebrationAnswered,
            self::BreakdownAnswered, self::ProvidersAnswered, self::AttendeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isCelebrationAnswered(): bool
    {
        return match ($this) {
            self::CelebrationAnswered, self::BreakdownAnswered, self::ProvidersAnswered,
            self::AttendeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isBreakdownAnswered(): bool
    {
        return match ($this) {
            self::BreakdownAnswered, self::ProvidersAnswered, self::AttendeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isProvidersAnswered(): bool
    {
        return match ($this) {
            self::ProvidersAnswered, self::AttendeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isAttendeesAnswered(): bool
    {
        return match ($this) {
            self::AttendeesAnswered, self::Completed => true,
            default => false,
        };
    }

    public function isCompleted(): bool
    {
        return self::Completed === $this;
    }

    public function activeStepNumber(): int
    {
        return match ($this) {
            self::Started             => 1,
            self::MaterialsAnswered   => 2,
            self::AssemblyAnswered    => 3,
            self::CelebrationAnswered => 4,
            self::BreakdownAnswered => 5,
            self::ProvidersAnswered => 6,
            self::AttendeesAnswered => 7,
            self::Completed => 8,
        };
    }

    public function activeStepRouteName(): string
    {
        return match ($this) {
            self::Started             => 'events_carbon_footprint_materials',
            self::MaterialsAnswered   => 'events_carbon_footprint_assembly',
            self::AssemblyAnswered    => 'events_carbon_footprint_celebration',
            self::CelebrationAnswered => 'events_carbon_footprint_breakdown',
            self::BreakdownAnswered => 'events_carbon_footprint_providers',
            self::ProvidersAnswered => 'events_carbon_footprint_attendees',
            self::AttendeesAnswered => 'events_carbon_footprint_results',
            self::Completed => 'events_carbon_footprint_results',
        };
    }
}
