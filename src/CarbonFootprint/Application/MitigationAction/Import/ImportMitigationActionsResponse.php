<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Import;

use App\Shared\Application\CommandBus\CommandResponse;

class ImportMitigationActionsResponse implements CommandResponse
{
    public function __construct(public int $imported) {}
}
