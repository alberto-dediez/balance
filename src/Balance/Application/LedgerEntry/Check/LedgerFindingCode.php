<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Check;

enum LedgerFindingCode: string
{
    case ContributionSumMismatch = 'contribution_sum_mismatch';

    case PaidContributionWithoutEntries = 'paid_contribution_without_entries';

    case OrphanEntries = 'orphan_entries';

    case OverallocatedCreditPurchase = 'overallocated_credit_purchase';

    case UnbalancedPayout = 'unbalanced_payout';

    case PayoutNumberingGap = 'payout_numbering_gap';
}
