<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\Access;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\Security\Domain\Model\Account\AccountId;

class AccessProtocolRequest
{
    public function __construct(
        public ProtocolId $protocolId,
        public AccountId $accountId,
    ) {}
}
