<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Remove;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveMitigationActionResponse implements CommandResponse
{
    public function __construct(public MitigationActionId $mitigationActionId) {}
}
