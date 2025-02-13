<?php

namespace App\Security\Domain\Model\User;

enum Role: string
{
    case User = 'ROLE_USER';

    case Admin = 'ROLE_ADMIN';

    case SuperAdmin = 'ROLE_SUPER_ADMIN';
}