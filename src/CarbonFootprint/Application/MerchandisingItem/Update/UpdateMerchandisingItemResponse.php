<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Update;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMerchandisingItemResponse implements CommandResponse
{
    public function __construct(
        public MerchandisingItemId $merchandisingItemId,
        public string $name,
    ) {}
}
