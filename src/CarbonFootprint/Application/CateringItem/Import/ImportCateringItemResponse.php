<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Import;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportCateringItemResponse implements CommandResponse
{
    public function __construct(
        public CateringItemId $cateringItemId,
    ) {}
}
