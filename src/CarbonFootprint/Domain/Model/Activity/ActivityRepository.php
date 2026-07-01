<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

interface ActivityRepository
{
    public function save(Activity $activity): void;

    public function findById(ActivityId $id): ?Activity;

    public function remove(Activity $activity): void;
}
