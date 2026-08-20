<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class BrandingId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
