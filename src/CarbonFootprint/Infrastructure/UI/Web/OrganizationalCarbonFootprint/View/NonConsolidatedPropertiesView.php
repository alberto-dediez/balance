<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope\NonConsolidatedPropertyRow;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class NonConsolidatedPropertiesView
{
    /**
     * @param list<NonConsolidatedPropertyRow> $rows
     *
     * @return array<string, array{rows: list<array{propertyId: string, propertyName: string, hasOrigin: bool, year: int, calculated: string, manual: ?string, applied: string}>, total: string}>
     */
    public static function build(array $rows): array
    {
        $built = [];
        $totals = [];

        foreach (Scope::Scope3->companyOnlySources() as $source) {
            $built[$source->value] = ['rows' => [], 'total' => '0'];
            $totals[$source->value] = new Emissions();
        }

        foreach ($rows as $row) {
            $sourceValue = $row->source->value;

            if (!isset($built[$sourceValue])) {
                continue;
            }

            $built[$sourceValue]['rows'][] = [
                'propertyId' => (string) $row->propertyId,
                'propertyName' => $row->property?->name() ?? (string) $row->propertyId,
                'hasOrigin' => null !== $row->originId,
                'year' => $row->year,
                'calculated' => $row->calculatedEmissions->co2eq(),
                'manual' => $row->manualCo2eq,
                'applied' => $row->emissions->co2eq(),
            ];

            $totals[$sourceValue] = ($totals[$sourceValue] ?? new Emissions())->add($row->emissions);
            $built[$sourceValue]['total'] = $totals[$sourceValue]->co2eq();
        }

        return $built;
    }
}
