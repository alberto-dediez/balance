<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

enum PayeeStatus: string
{
    case Incomplete = 'incomplete';

    case Ready = 'ready';

    case Blocked = 'blocked';
}
