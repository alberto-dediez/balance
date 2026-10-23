<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkDestinationCountryId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class TripCalculationLinkDestinationCountryIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'trip_calculation_link_destination_country_id';
    }

    public function getClassName(): string
    {
        return TripCalculationLinkDestinationCountryId::class;
    }
}
