<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySettings;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class MaterialitySettingsId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
