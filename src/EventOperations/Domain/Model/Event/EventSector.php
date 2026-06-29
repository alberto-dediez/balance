<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

enum EventSector: string
{
    case Sports = 'sports';

    case Music = 'music';

    case Cultural = 'cultural';

    case Tourism = 'tourism';

    case Social = 'social';

    case Business = 'business';

    case Academic = 'academic';

    case Religious = 'religious';

    case Technology = 'technology';

    case Charity = 'charity';

    case Political = 'political';

    case Convention = 'convention';

    case Fair = 'fair';

    case Institutional = 'institutional';

    case Other = 'other';
}
