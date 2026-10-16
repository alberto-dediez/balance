<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;

enum Scope: string implements CalculationStage
{
    case Scope1 = 'scope_1';
    case Scope2 = 'scope_2';

    public function key(): string
    {
        return $this->value;
    }

    /** @return list<SectionDefinition> */
    public function sections(): array
    {
        return match ($this) {
            self::Scope1 => [
                new SectionDefinition('combustibles', Source::FixedFacilities, ActivityType::Combustible),
                new SectionDefinition('vehicles', Source::OwnVehicles, ActivityType::Transport),
                new SectionDefinition('refrigerantGases', Source::RefrigerantLeaks, ActivityType::RefrigerantGas),
            ],
            self::Scope2 => [
                new SectionDefinition('electricity', Source::BuildingElectricity, ActivityType::Electricity),
            ],
        };
    }

    public static function ofSource(Source $source): ?self
    {
        foreach (self::cases() as $scope) {
            foreach ($scope->sections() as $section) {
                if ($section->source === $source) {
                    return $scope;
                }
            }
        }

        return null;
    }

    public function stepNumber(): int
    {
        return match ($this) {
            self::Scope1 => 1,
            self::Scope2 => 2,
        };
    }

    public function answeredStatus(): OrganizationalCarbonFootprintStatus
    {
        return match ($this) {
            self::Scope1 => OrganizationalCarbonFootprintStatus::Scope1Answered,
            self::Scope2 => OrganizationalCarbonFootprintStatus::Scope2Answered,
        };
    }
}
