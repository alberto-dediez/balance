<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

enum OperatingRegime: string
{
    case Owned = 'owned';

    case Leased = 'leased';

    case Managed = 'managed';

    case Franchised = 'franchised';

    case LeasedOut = 'leased_out';

    public function isConsolidated(): bool
    {
        return match ($this) {
            OperatingRegime::Owned, OperatingRegime::Leased, OperatingRegime::Managed => true,
            OperatingRegime::Franchised, OperatingRegime::LeasedOut => false,
        };
    }
}
