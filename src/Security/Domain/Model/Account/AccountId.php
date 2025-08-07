<?php

namespace App\Security\Domain\Model\Account;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class AccountId implements AggregateRootId
{
    use AggregateRootIdTrait;
}