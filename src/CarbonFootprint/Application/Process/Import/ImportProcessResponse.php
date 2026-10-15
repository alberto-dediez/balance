<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Import;

use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportProcessResponse implements CommandResponse
{
    public function __construct(
        public ProcessId $processId,
    ) {}
}
