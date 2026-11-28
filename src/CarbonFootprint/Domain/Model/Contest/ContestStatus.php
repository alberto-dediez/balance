<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

enum ContestStatus: string
{
    case Draft = 'draft';

    case Published = 'published';

    case Closed = 'closed';

    case Resolved = 'resolved';

    public function style(): string
    {
        return match ($this) {
            self::Draft => 'bg-secondary',
            self::Published => 'bg-success',
            self::Closed => 'bg-warning text-dark',
            self::Resolved => 'bg-info',
        };
    }
}
