<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

enum LedgerEntryType: string
{
    case ContributionNet = 'contribution_net';

    case ManagementFee = 'management_fee';

    case Vat = 'vat';

    case Adjustment = 'adjustment';
}
