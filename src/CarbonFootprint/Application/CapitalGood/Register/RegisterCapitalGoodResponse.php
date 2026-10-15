<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Register;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterCapitalGoodResponse implements CommandResponse
{
    public function __construct(
        public CapitalGoodId $capitalGoodId,
        public string $name,
    ) {}
}
