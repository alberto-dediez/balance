<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class CreditPurchaseFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?ProjectId $projectId = null,
        public ?CreditPurchaseStatus $status = null,
        public ?string $reference = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
