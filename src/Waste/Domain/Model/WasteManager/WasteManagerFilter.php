<?php

namespace App\Waste\Domain\Model\WasteManager;

use App\Shared\Domain\Model\Filter;
use App\Security\Domain\Model\Account\AccountId;

class WasteManagerFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?bool $isPublic = null,
        public ?AccountId $accountId = null,
        int $limit = 10
    ) {
        $this->limit = $limit;
    }
}