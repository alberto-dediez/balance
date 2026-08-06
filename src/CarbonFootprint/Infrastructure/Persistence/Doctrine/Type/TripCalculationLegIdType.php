<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationLegId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class TripCalculationLegIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'trip_calculation_leg_id';
    }

    public function getClassName(): string
    {
        return TripCalculationLegId::class;
    }
}
