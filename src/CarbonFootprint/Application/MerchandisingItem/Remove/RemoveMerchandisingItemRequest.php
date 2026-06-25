<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Remove;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\Shared\Application\CommandBus\Command;

class RemoveMerchandisingItemRequest implements Command
{
    public function __construct(public MerchandisingItemId $merchandisingItemId) {}
}
