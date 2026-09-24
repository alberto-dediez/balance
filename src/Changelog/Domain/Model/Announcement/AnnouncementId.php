<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class AnnouncementId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
