<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class MaterialityTopicId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
