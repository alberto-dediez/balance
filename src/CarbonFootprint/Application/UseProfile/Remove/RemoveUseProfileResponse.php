<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\Remove;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveUseProfileResponse implements CommandResponse
{
    public function __construct(public UseProfileId $useProfileId) {}
}
