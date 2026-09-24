<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

enum TwoFactorMethod: string
{
    case Email = 'Email';

    case Totp = 'Totp';
}
