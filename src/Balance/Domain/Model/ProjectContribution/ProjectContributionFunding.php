<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

enum ProjectContributionFunding: string
{
    case Payment = 'payment';
    case OwnCredit = 'own_credit';

    public function isOwnCredit(): bool
    {
        return self::OwnCredit === $this;
    }
}
