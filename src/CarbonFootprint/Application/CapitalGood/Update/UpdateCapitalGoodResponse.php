<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Update;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateCapitalGoodResponse implements CommandResponse
{
    public function __construct(
        public CapitalGoodId $capitalGoodId,
        public string $name,
    ) {}
}
