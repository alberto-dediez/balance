<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class ApiTokenId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
