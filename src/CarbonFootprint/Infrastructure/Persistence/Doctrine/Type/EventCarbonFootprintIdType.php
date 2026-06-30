<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class EventCarbonFootprintIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'event_carbon_footprint_id';
    }

    public function getClassName(): string
    {
        return EventCarbonFootprintId::class;
    }
}
