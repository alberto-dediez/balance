<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\Export;

enum CarbonFootprintDetailRowType: string
{
    case Activity = 'activity';
    case DerivedFuelAndEnergy = 'derived_fuel_and_energy';
    case NonConsolidatedProperty = 'non_consolidated_property';
    case Exclusion = 'exclusion';
}
