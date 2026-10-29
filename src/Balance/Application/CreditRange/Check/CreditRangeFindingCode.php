<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditRange\Check;

enum CreditRangeFindingCode: string
{
    case RangeMismatch = 'range_mismatch';

    case OverallocatedAfterRepair = 'overallocated_after_repair';

    case MissingTransferEvent = 'missing_transfer_event';

    case OvercededRange = 'overceded_range';
}
