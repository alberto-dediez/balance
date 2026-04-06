<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\RecipientOrganization;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class RecipientOrganizationFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?string $contact = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
