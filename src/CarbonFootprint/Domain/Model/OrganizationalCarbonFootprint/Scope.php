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
    case Scope3 = 'scope_3';

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
            self::Scope3 => [
                new SectionDefinition('purchasedGoodsAndServices', Source::PurchasedGoodsAndServices, ActivityType::PurchaseService),
                new SectionDefinition('capitalGoods', Source::CapitalGoods, ActivityType::CapitalGood),
                new SectionDefinition('upstreamTransport', Source::UpstreamTransport, ActivityType::Transport),
                new SectionDefinition('operationsWaste', Source::OperationsWaste, ActivityType::Waste),
                new SectionDefinition('businessTravel', Source::BusinessTravel, ActivityType::Mobility),
                new SectionDefinition('businessTravelStays', Source::BusinessTravel, ActivityType::OvernightStay),
                new SectionDefinition('employeeCommuting', Source::EmployeeCommuting, ActivityType::Mobility),
                new SectionDefinition('downstreamTransport', Source::DownstreamTransport, ActivityType::Transport),
                new SectionDefinition('soldProductsProcessing', Source::SoldProductsProcessing, ActivityType::Process),
                new SectionDefinition('soldProductsUse', Source::SoldProductsUse, ActivityType::UseProfile),
                new SectionDefinition('soldProductsEndOfLife', Source::SoldProductsEndOfLife, ActivityType::Waste),
                new SectionDefinition('investments', Source::Investments, ActivityType::EconomicSector),
            ],
        };
    }

    /** @return list<Source> */
    public function derivedSources(): array
    {
        return match ($this) {
            self::Scope1, self::Scope2 => [],
            self::Scope3 => [Source::FuelAndEnergyRelated],
        };
    }

    /** @return list<Source> */
    public function allSources(): array
    {
        $sources = [];
        foreach ($this->sections() as $section) {
            if (!in_array($section->source, $sources, true)) {
                $sources[] = $section->source;
            }
        }

        foreach ($this->derivedSources() as $source) {
            if (!in_array($source, $sources, true)) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    /** @return list<Source> */
    public function upstreamSources(): array
    {
        return match ($this) {
            self::Scope1, self::Scope2 => [],
            self::Scope3 => [
                Source::PurchasedGoodsAndServices,
                Source::CapitalGoods,
                Source::FuelAndEnergyRelated,
                Source::UpstreamTransport,
                Source::OperationsWaste,
                Source::BusinessTravel,
                Source::EmployeeCommuting,
            ],
        };
    }

    /** @return list<Source> */
    public function downstreamSources(): array
    {
        return match ($this) {
            self::Scope1, self::Scope2 => [],
            self::Scope3 => [
                Source::DownstreamTransport,
                Source::SoldProductsProcessing,
                Source::SoldProductsUse,
                Source::SoldProductsEndOfLife,
                Source::Investments,
            ],
        };
    }

    public function isExcludable(): bool
    {
        return self::Scope3 === $this;
    }

    public static function ofSource(Source $source): ?self
    {
        foreach (self::cases() as $scope) {
            if (in_array($source, $scope->allSources(), true)) {
                return $scope;
            }
        }

        return null;
    }

    public function stepNumber(): int
    {
        return match ($this) {
            self::Scope1 => 1,
            self::Scope2 => 2,
            self::Scope3 => 3,
        };
    }

    public function answeredStatus(): OrganizationalCarbonFootprintStatus
    {
        return match ($this) {
            self::Scope1 => OrganizationalCarbonFootprintStatus::Scope1Answered,
            self::Scope2 => OrganizationalCarbonFootprintStatus::Scope2Answered,
            self::Scope3 => OrganizationalCarbonFootprintStatus::Scope3Answered,
        };
    }
}
