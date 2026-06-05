<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class SignatureRequestId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
