<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;

class ActivityNotFoundException extends \RuntimeException
{
    public function __construct(ActivityId $id)
    {
        parent::__construct(sprintf('Activity "%s" not found.', (string) $id));
    }
}
