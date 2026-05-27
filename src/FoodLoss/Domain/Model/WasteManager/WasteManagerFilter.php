<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\WasteManager;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class WasteManagerFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?AccountId $accountId = null,
        public ?bool $animalFeedAuthorized = null,
        public ?bool $compostAuthorized = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
