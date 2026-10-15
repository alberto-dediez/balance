<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Remove;

use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\Shared\Application\CommandBus\Command;

class RemoveProcessRequest implements Command
{
    public function __construct(public ProcessId $processId) {}
}
