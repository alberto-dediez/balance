<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

enum ContributionCertificateStatus: string
{
    case Pending = 'pending';

    case Generated = 'generated';

    case Failed = 'failed';

    public function isGenerated(): bool
    {
        return self::Generated === $this;
    }
}
