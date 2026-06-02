<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class AlertConfigurationItemId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
