<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

enum DiscardReason: string
{
    case User = 'user';
    case Superseded = 'superseded';
}
