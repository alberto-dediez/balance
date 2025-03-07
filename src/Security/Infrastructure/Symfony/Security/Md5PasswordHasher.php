<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Application\User\Register\PasswordHasher;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class Md5PasswordHasher implements PasswordHasher
{
    public function hashPassword(string $password): string
    {
        return md5($password);
    }
}