<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DoggyBagException;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class DoggyBagExceptionFilter
{
    use Filter;

    public function __construct(
        public ?string $description = null,
        public ?AccountId $accountId = null,
        public ?PropertyId $propertyId = null,
        public ?CriticalPointId $criticalPointId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
