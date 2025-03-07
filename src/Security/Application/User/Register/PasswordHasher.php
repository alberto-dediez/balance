<?php

namespace App\Security\Application\User\Register;

interface PasswordHasher
{
    public function hashPassword(string $password): string;
}