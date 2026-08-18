<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payee;

enum PayeeType: string
{
    case Account = 'account';

    case Project = 'project';
}
