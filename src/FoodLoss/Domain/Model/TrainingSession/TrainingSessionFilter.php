<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingSession;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class TrainingSessionFilter
{
    use Filter;

    public function __construct(
        public ?string $title = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
