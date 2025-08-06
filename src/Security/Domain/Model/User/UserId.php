<?php

namespace App\Security\Domain\Model\User;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class UserId implements AggregateRootId
{
    use AggregateRootIdTrait;
}