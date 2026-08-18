<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

enum LedgerBeneficiary: string
{
    case Bioscore = 'bioscore';

    case Account = 'account';

    case Project = 'project';

    case Unassigned = 'unassigned';

    public function isSettleable(): bool
    {
        return self::Account === $this || self::Project === $this;
    }
}
