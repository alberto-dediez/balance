<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class AlertConfigurationFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        int $limit = 50,
        string $orderBy = 'registeredAt',
        string $orderWay = 'DESC',
    ) {
        $this->limit = $limit;
        $this->orderBy = $orderBy;
        $this->orderWay = $orderWay;
    }
}
