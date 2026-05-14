<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ProtocolAccess\ListProtocolAccesses;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\Security\Domain\Model\Account\AccountId;

class ListProtocolAccessesRequest
{
    public function __construct(
        public ProtocolId $protocolId,
        public AccountId $accountId,
    ) {}
}
