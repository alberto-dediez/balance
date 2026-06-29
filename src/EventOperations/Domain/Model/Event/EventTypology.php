<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

enum EventTypology: string
{
    case Local = 'local';

    case Regional = 'regional';

    case National = 'national';

    case International = 'international';
}
