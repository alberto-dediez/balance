<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class TripCalculationLinkIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'trip_calculation_link_id';
    }

    public function getClassName(): string
    {
        return TripCalculationLinkId::class;
    }
}
