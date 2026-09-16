<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;

interface OrganizationalCarbonFootprintActivity
{
    public function activityId(): ActivityId;

    public function placement(): ActivityPlacement;
}
