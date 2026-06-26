<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Register;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterCateringItemResponse implements CommandResponse
{
    public function __construct(
        public CateringItemId $cateringItemId,
        public string $name,
    ) {}
}
