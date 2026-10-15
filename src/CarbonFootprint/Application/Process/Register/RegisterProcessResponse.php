<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Register;

use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterProcessResponse implements CommandResponse
{
    public function __construct(
        public ProcessId $processId,
        public string $name,
    ) {}
}
