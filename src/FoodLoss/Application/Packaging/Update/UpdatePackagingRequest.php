<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\Update;

use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingPropertyStockData;
use App\FoodLoss\Domain\Model\Packaging\PackagingType;
use App\Shared\Application\CommandBus\Command;

class UpdatePackagingRequest implements Command
{
    /**
     * @param PackagingPropertyStockData[] $stocks
     */
    public function __construct(
        public PackagingId $packagingId,
        public string $name,
        public PackagingType $packagingType,
        public array $stocks = [],
    ) {}
}
