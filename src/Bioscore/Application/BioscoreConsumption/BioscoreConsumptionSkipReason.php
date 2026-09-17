<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreConsumption;

enum BioscoreConsumptionSkipReason: string
{
    case Unmapped = 'unmapped';
    case TargetMissing = 'target_missing';
    case IncompatibleUnit = 'incompatible_unit';
}
