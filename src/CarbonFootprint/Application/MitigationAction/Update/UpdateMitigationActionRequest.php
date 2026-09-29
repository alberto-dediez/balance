<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Update;

use App\CarbonFootprint\Application\MitigationAction\MitigationActionEffectData;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\Shared\Application\CommandBus\Command;

class UpdateMitigationActionRequest implements Command
{
    /** @param list<MitigationActionEffectData> $effects */
    public function __construct(
        public MitigationActionId $mitigationActionId,
        public string $name,
        public string $description,
        public int $targetYear,
        public array $effects = [],
    ) {}
}
