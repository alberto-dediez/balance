<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\Register;

use App\FoodLoss\Domain\Model\Packaging\PackagingPropertyStockData;
use App\FoodLoss\Domain\Model\Packaging\PackagingType;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterPackagingRequest implements Command
{
    /**
     * @param PackagingPropertyStockData[] $stocks
     */
    public function __construct(
        public string $name = '',
        public PackagingType $packagingType = PackagingType::Reusable,
        public ?AccountId $accountId = null,
        public array $stocks = [],
    ) {}
}
