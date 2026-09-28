<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

enum MaterialityTopicDirection: string
{
    case Upstream = 'upstream';

    case Downstream = 'downstream';
}
