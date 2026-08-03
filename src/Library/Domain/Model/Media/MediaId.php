<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class MediaId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
