<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Update;

use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateProcessRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public ProcessId $processId,
        public string $name,
        public Unit $unit,
        public array $emissionFactors = [],
    ) {}
}
