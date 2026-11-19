<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

enum IntegrationInboxStatus: string
{
    case Processed = 'processed';
    case SkippedStale = 'skipped_stale';
    case SkippedUnknownType = 'skipped_unknown_type';
    case SkippedHops = 'skipped_hops';
}
