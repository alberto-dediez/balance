<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class IncidenceFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?AccountId $accountId = null,
        public ?PropertyId $propertyId = null,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
