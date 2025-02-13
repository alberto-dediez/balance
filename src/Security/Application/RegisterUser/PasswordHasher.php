<?php

namespace App\Security\Application\RegisterUser;

interface PasswordHasher
{
    public function hashPassword(string $password): string;
}