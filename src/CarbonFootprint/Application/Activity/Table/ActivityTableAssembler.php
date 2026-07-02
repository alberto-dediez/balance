<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('carbon_footprint.activity_table_assembler')]
interface ActivityTableAssembler
{
    public function activityType(): ActivityType;

    /** @return list<mixed> catalog with a valid emission factor for the ECF year/group */
    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array;

    /** @return list<ActivityTableRow> rows already saved for this type (and, if applicable, source) */
    public function buildRows(EventCarbonFootprint $eventCarbonFootprint, ActivityRepository $activityRepository, ?Source $source = null): array;
}
