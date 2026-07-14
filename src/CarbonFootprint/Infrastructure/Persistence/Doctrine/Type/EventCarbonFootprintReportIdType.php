<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Type;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class EventCarbonFootprintReportIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'event_carbon_footprint_report_id';
    }

    public function getClassName(): string
    {
        return EventCarbonFootprintReportId::class;
    }
}
