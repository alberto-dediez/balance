<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\Update;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateProtocolResponse implements CommandResponse
{
    public function __construct(
        public ProtocolId $protocolId,
        public string $name,
    ) {}
}
