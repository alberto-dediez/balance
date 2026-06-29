<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

enum EventSize: string
{
    case Micro = 'Micro';

    case Small = 'Small';

    case Medium = 'Medium';

    case Large = 'Large';

    case Macro = 'Macro';
}
