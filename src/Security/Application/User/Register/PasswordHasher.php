<?php

declare(strict_types=1);

namespace App\Security\Application\User\Register;

interface PasswordHasher
{
    public function hashPassword(string $password): string;
}
