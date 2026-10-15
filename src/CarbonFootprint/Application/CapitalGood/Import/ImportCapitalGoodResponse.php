<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Import;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportCapitalGoodResponse implements CommandResponse
{
    public function __construct(
        public CapitalGoodId $capitalGoodId,
    ) {}
}
