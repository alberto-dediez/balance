<?php

declare(strict_types=1);

namespace App\Waste\Application\WasteManager\Update;

use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class UpdateWasteManagerResponse implements CommandResponse
{
    public function __construct(public WasteManagerId $wasteManagerId, public string $name, public bool $isPublic) {}
}
