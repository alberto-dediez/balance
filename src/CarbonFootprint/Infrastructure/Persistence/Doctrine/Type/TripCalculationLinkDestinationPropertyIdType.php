<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkDestinationPropertyId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class TripCalculationLinkDestinationPropertyIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'trip_calculation_link_destination_property_id';
    }

    public function getClassName(): string
    {
        return TripCalculationLinkDestinationPropertyId::class;
    }
}
