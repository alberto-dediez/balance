<?php

declare(strict_types=1);

namespace App\Waste\Application\WasteManager\Update;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class UpdateWasteManagerRequest implements Command
{
    public function __construct(public WasteManagerId $wasteManagerId, public string $name, public bool $isPublic) {}
}
