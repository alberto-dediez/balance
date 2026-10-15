<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Remove;

use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveProcessResponse implements CommandResponse
{
    public function __construct(public ProcessId $processId) {}
}
