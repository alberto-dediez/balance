<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\WasteManager;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class WasteManagerFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?bool $isPublic = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
