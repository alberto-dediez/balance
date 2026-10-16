<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Register;

use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterTransportResponse implements CommandResponse
{
    public function __construct(
        public TransportId $transportId,
        public string $name,
    ) {}
}
