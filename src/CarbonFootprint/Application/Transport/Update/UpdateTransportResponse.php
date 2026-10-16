<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Update;

use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateTransportResponse implements CommandResponse
{
    public function __construct(
        public TransportId $transportId,
        public string $name,
    ) {}
}
