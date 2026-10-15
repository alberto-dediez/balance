<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Remove;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveCapitalGoodResponse implements CommandResponse
{
    public function __construct(public CapitalGoodId $capitalGoodId) {}
}
