<?php

declare(strict_types=1);

namespace App\Property\Application\Property\ListDistributorProperties;

use App\Security\Domain\Model\Account\AccountId;

final readonly class ListDistributorPropertiesRequest
{
    public function __construct(public AccountId $distributorAccountId) {}
}
