<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Provider;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class ProviderFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
