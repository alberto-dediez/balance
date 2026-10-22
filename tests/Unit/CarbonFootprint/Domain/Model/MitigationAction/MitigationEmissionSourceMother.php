<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

class MitigationEmissionSourceMother
{
    public static function from(Source $source, string $itemId, float $emissions, ?string $itemName = null): MitigationEmissionSource
    {
        $scope = Scope::ofSource($source);
        assert(null !== $scope);

        return new MitigationEmissionSource(
            scope: $scope,
            source: $source,
            itemId: $itemId,
            itemName: $itemName ?? 'item-' . $itemId,
            emissions: $emissions,
        );
    }

    public static function naturalGas(float $emissions = 1000.0): MitigationEmissionSource
    {
        return self::from(Source::FixedFacilities, 'natural-gas', $emissions, 'Gas natural');
    }

    public static function electricity(float $emissions = 500.0): MitigationEmissionSource
    {
        return self::from(Source::BuildingElectricity, 'marketer', $emissions, 'Comercializadora');
    }

    public static function derivedFrom(MitigationEmissionSource $origin, float $emissions): MitigationEmissionSource
    {
        return new MitigationEmissionSource(
            scope: Scope::Scope3,
            source: Source::FuelAndEnergyRelated,
            itemId: $origin->itemId,
            itemName: $origin->itemName,
            emissions: $emissions,
            originKey: $origin->key(),
        );
    }
}
