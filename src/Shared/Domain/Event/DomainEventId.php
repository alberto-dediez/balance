<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Shared\Domain\Uuid\UuidTrait;

final class DomainEventId
{
    use UuidTrait;
}
