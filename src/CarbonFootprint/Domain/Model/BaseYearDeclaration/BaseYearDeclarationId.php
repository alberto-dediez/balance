<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class BaseYearDeclarationId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
